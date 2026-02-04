<?php

namespace App\Traits;

use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasTransaksiKasBank
{
    /** Apakah akun butuh cek saldo saat menjadi sumber (non-kas-bank asset, dll.) */
    private function needsSaldoCheck(int $akunId): bool
    {
        $akun = AkunKeuangan::find($akunId);
        if (!$akun)
            return false;

        return ($akun->tipe_akun ?? '') === 'asset';
    }

    /** Daftar akun Kas/Bank (pindahkan ke config bila perlu) */
    private function isKasBank(int $akunId): bool
    {
        $akunKasBank = [1011, 1012, 1013, 1014, 1015, 1021, 1022, 1023, 1024, 1025];
        return in_array($akunId, $akunKasBank, true);
    }

    /**
     * Ambil saldo terakhir dari KOLOM `saldo` untuk akun tertentu (<= cutoff),
     * termasuk baris '-LAWAN' (karena saldo akun lawan juga ada di sana).
     * - Non-Bendahara: filter per-bidang + fallback histori lama (NULL)
     * - Bendahara: global
     */
    protected function getLastSaldoBySaldoColumn(
        ?int $akunId,
        string $userRole,
        $bidangValue,
        ?string $tanggalCutoff = null
    ): float {
        if (!$akunId)
            return 0.0;

        $q = Transaksi::where('akun_keuangan_id', $akunId);

        if ($tanggalCutoff) {
            $cutoff = Carbon::parse($tanggalCutoff)->toDateString();
            $q->whereDate('tanggal_transaksi', '<=', $cutoff);
        }

        if ($userRole !== 'Bendahara') {
            $q->where(function ($w) use ($bidangValue) {
                $w->where('bidang_name', $bidangValue)
                    ->orWhereNull('bidang_name');
            });
        }

        return (float) ($q->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0.0);
    }

    /** Ambil id akun Kas default berdasar role & bidang */
    protected function getDefaultKasAkunId(string $role, ?int $bidangId): ?int
    {
        if ($role === 'Bendahara')
            return 1011;
        $map = [1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015];

        return $bidangId && isset($map[$bidangId]) ? $map[$bidangId] : null;
    }

    /** Ambil id akun Bank default berdasar role & bidang */
    protected function getDefaultBankAkunId(string $role, ?int $bidangId): ?int
    {
        if ($role === 'Bendahara')
            return 1021;
        $map = [1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025];

        return $bidangId && isset($map[$bidangId]) ? $map[$bidangId] : null;
    }

    /** Prefix kode transaksi (ikut mapping kamu) */
    protected function makeKodePrefix(string $role, ?int $bidangId): string
    {
        if ($role === 'Bidang') {
            return match ($bidangId) {
                1 => 'SJD', // Pendidikan
                2 => 'PND', // Kemasjidan
                3 => 'SOS', // Sosial
                4 => 'UHA', // Usaha
                5 => 'BGN', // Pembangunan
                default => '',
            };
        }

        return $role === 'Bendahara' ? 'BDH' : '';
    }

    // Pilih akun sisi DEBIT (Kas / Bank) berdasarkan metode_bayar
    protected function resolveAkunPenerimaanByMetode(
        string $role,
        ?int $bidangId,
        ?string $metodeBayar
    ): ?int {
        $metode = strtolower((string) $metodeBayar);

        // Transfer → prioritas Bank
        if ($metode === 'transfer') {
            $bank = $this->getDefaultBankAkunId($role, $bidangId);
            if ($bank) {
                return $bank;
            }
        }

        // Tunai / lainnya → prioritas Kas
        $kas = $this->getDefaultKasAkunId($role, $bidangId);
        if ($kas) {
            return $kas;
        }

        // Fallback terakhir: kalau Kas nggak ada tapi Bank ada
        $bank = $this->getDefaultBankAkunId($role, $bidangId);
        return $bank ?: null;
    }

    /**
     * LOGIKA INTI (Baru): Proses validasi dan simpan tanpa Redirect.
     * Method ini melempar Exception jika ada validasi bisnis yang gagal (saldo kurang, dll).
     */
    protected function processTransactionCore(Request $request, int $akun_keuangan_id, ?int $parent_akun_id)
    {
        $userRole = auth()->user()->role ?? 'Guest';

        // 1. Validasi Input
        // Kita gunakan validator manual agar bisa throw Exception, bukan auto-redirect
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'kode_transaksi' => 'required|string', // Hapus unique strict disini jika ingin handle manual, atau biarkan
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'bidang_name' => $userRole !== 'Bendahara' ? 'required|integer' : 'nullable',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $validated = $validator->validated();

        // 2. Konteks & Casting
        $bidangValue = ($userRole === 'Bendahara') ? null : (int) ($validated['bidang_name'] ?? null);
        $tanggal = Carbon::parse($validated['tanggal_transaksi'])->toDateString();
        $amount = (float) $validated['amount'];
        $tipe = $validated['type'];

        // 3. Cek Saldo (Logic Lama)
        $saldoSebelumnyaAkun = $this->getLastSaldoBySaldoColumn($akun_keuangan_id, $userRole, $bidangValue, $tanggal);
        $saldoSebelumnyaLawan = $this->getLastSaldoBySaldoColumn($parent_akun_id, $userRole, $bidangValue, $tanggal);
        // ===============================================
        // DEBUG BLOCK: Tambahkan logging di sini
        Log::warning('DEBUG VALIDASI SALDO:', [
            'Akun ID SUMBER' => $akun_keuangan_id,
            'SALDO (Sistem)' => $saldoSebelumnyaAkun,
            'AMOUNT (Pengeluaran)' => $amount,
            'HASIL PERBANDINGAN' => $amount > $saldoSebelumnyaAkun, // True jika gagal
        ]);
        // ===============================================
        // 4. Validasi Bisnis (Ganti return back() dengan throw Exception)
        if ($tipe === 'pengeluaran' && $amount > $saldoSebelumnyaAkun) {
            throw new \Exception('Jumlah pengeluaran tidak boleh melebihi saldo akun utama.');
        }

        if ($tipe === 'penerimaan' && $parent_akun_id) {
            if ($this->isKasBank($parent_akun_id)) {
                if ($amount > $saldoSebelumnyaLawan) {
                    throw new \Exception('Saldo sumber (Kas/Bank) tidak mencukupi untuk transfer.');
                }
            } elseif ($this->needsSaldoCheck($parent_akun_id) && $amount > $saldoSebelumnyaLawan) {
                throw new \Exception('Jumlah penerimaan tidak boleh melebihi saldo akun asal.');
            }
        }

        // 5. Hitung Saldo Baru
        $newSaldoAkun = ($tipe === 'penerimaan') ? $saldoSebelumnyaAkun + $amount : $saldoSebelumnyaAkun - $amount;

        $newSaldoLawan = 0.0;
        if ($parent_akun_id) {
            $newSaldoLawan = ($tipe === 'penerimaan') ? $saldoSebelumnyaLawan - $amount : $saldoSebelumnyaLawan + $amount;
        }

        // 6. Guard Negatif
        if ($tipe === 'pengeluaran' && $this->isKasBank($akun_keuangan_id) && $newSaldoAkun < 0) {
            throw new \Exception('Transaksi gagal! Saldo Kas/Bank tidak boleh negatif.');
        }
        if ($parent_akun_id && $this->isKasBank($parent_akun_id) && $newSaldoLawan < 0) {
            throw new \Exception('Transaksi gagal! Saldo Kas/Bank (sumber) tidak boleh negatif.');
        }

        // 7. Simpan Transaksi (Tanpa DB::transaction di sini, biar Controller yang atur atomicity)
        // Utama
        $trxAkun = Transaksi::create([
            'bidang_name' => $bidangValue,
            'kode_transaksi' => $validated['kode_transaksi'],
            'tanggal_transaksi' => $tanggal,
            'type' => $tipe,
            'akun_keuangan_id' => $akun_keuangan_id,
            'parent_akun_id' => $parent_akun_id,
            'deskripsi' => $validated['deskripsi'],
            'amount' => $amount,
            'saldo' => (float) $newSaldoAkun,
        ]);

        // Lawan
        if ($parent_akun_id) {
            $typeLawan = ($tipe === 'penerimaan') ? 'pengeluaran' : 'penerimaan';
            $trxLawan = Transaksi::create([
                'bidang_name' => $bidangValue,
                'kode_transaksi' => $validated['kode_transaksi'] . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => $typeLawan,
                'akun_keuangan_id' => $parent_akun_id,
                'parent_akun_id' => $akun_keuangan_id,
                'deskripsi' => '(Lawan) ' . $validated['deskripsi'],
                'amount' => $amount,
                'saldo' => (float) $newSaldoLawan,
            ]);

            // Ledger Lawan
            Ledger::create([
                'transaksi_id' => $trxLawan->id,
                'akun_keuangan_id' => $trxLawan->akun_keuangan_id,
                'debit' => $trxLawan->type === 'penerimaan' ? $amount : 0,
                'credit' => $trxLawan->type === 'pengeluaran' ? $amount : 0,
            ]);
        }

        // Ledger Utama
        Ledger::create([
            'transaksi_id' => $trxAkun->id,
            'akun_keuangan_id' => $trxAkun->akun_keuangan_id,
            'debit' => $trxAkun->type === 'penerimaan' ? $amount : 0,
            'credit' => $trxAkun->type === 'pengeluaran' ? $amount : 0,
        ]);

        return $trxAkun;
    }

    /**
     * WRAPPER PUBLIC (Untuk menjaga kompatibilitas kode lama).
     * Menangani Exception dan me-return RedirectResponse.
     */
    public function storeTransaction(Request $request, $akun_keuangan_id, $parent_akun_id)
    {
        DB::beginTransaction();
        try {
            $this->processTransactionCore($request, (int) $akun_keuangan_id, $parent_akun_id ? (int) $parent_akun_id : null);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Untuk pemakaian internal (service/controller) agar bisa ikut rollback transaksi luar.
     * Akan melempar Exception jika gagal (saldo kurang, validasi, dll).
     */
    protected function storeTransactionOrFail(Request $request, int $akunId, ?int $parentId)
    {
        return $this->processTransactionCore($request, $akunId, $parentId);
    }

}
