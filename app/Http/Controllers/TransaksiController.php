<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exports\BukuHarianExport;
use App\Exports\TransaksisExport;
use App\Services\LaporanKeuanganService;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class TransaksiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name; // integer id bidang
        $role = $user->role;
        $bidangName = $user->bidang_name;

        $lapService = new LaporanKeuanganService();

        // ==========================
        // Ambil transaksi (untuk tabel)
        // ==========================
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        if ($role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $bidangName);
        }

        $transaksi = $transaksiQuery->get();

        // Semua akun (kalau masih dipakai di tempat lain)
        $akunKeuangan = AkunKeuangan::all();

        // ==========================
        // 🔹 Aset Neto: Induk & Anak (untuk dropdown tujuan OB)
        // ==========================
        $akunTanpaParent = AkunKeuangan::where('tipe_akun', 'equity')
            ->whereNull('parent_id')
            ->orderBy('kode_akun')
            ->get();

        $akunDenganParent = AkunKeuangan::where('tipe_akun', 'equity')
            ->whereNotNull('parent_id')
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('parent_id');

        // ==========================
        // 🔹 Prefix kode transaksi (transaksi biasa)
        // ==========================
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidangId) {
                case 1:
                    $prefix = 'SJD';
                    break; // Pendidikan
                case 2:
                    $prefix = 'PND';
                    break; // Kemasjidan
                case 3:
                    $prefix = 'SOS';
                    break; // Sosial
                case 4:
                    $prefix = 'UHA';
                    break; // Usaha
                case 5:
                    $prefix = 'BGN';
                    break; // Pembangunan
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH';
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // ==========================
        // 🔹 Mapping Kas & Bank per role/bidang
        // ==========================
        $kasMap = [
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ];

        $bankMap = [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        if ($role === 'Bendahara') {
            $kasAkunId = 1011; // Kas Bendahara
            $bankAkunId = 1021; // Bank Bendahara
        } else {
            $kasAkunId = $kasMap[$bidangId] ?? null;
            $bankAkunId = $bankMap[$bidangId] ?? null;
        }

        $kasAkun = $kasAkunId ? AkunKeuangan::find($kasAkunId) : null;
        $bankAkun = $bankAkunId ? AkunKeuangan::find($bankAkunId) : null;

        // ==========================
        // 🔹 Saldo Kas via LaporanKeuanganService
        // ==========================
        if ($kasAkun) {
            $saldoKas = $lapService->getSaldoAkunSampai($kasAkun, Carbon::now());
        } else {
            $saldoKas = 0;
        }

        // 🔹 Saldo Bank via LaporanKeuanganService
        if ($bankAkun) {
            $saldoBank = $lapService->getSaldoAkunSampai($bankAkun, Carbon::now());
        } else {
            $saldoBank = 0;
        }

        // ==========================
        // 🔹 Data untuk modal TRANSFER
        // ==========================
        // ==========================

        // 🔹 Daftar akun kas/bank tujuan (semua anak kas/bank)
        $kasBankTujuan = AkunKeuangan::where('is_kas_bank', 1)
            ->whereNotNull('parent_id')
            ->orderBy('kode_akun')
            ->get();

        // kode transaksi khusus transfer
        $kodeTransaksiTransfer = 'TRF-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('transaksi.index', compact(
            'transaksi',
            'akunTanpaParent',
            'akunDenganParent',
            'bidangName',
            'akunKeuangan',
            'kodeTransaksi',
            'saldoKas',
            'saldoBank',
            'kasAkun',
            'bankAkun',
            'kasBankTujuan',
            'kodeTransaksiTransfer',
            'bankAkunId',
        ));
    }

    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil saldo terakhir berdasarkan bidang_name
        $lastSaldo = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->latest()
            ->first()->saldo ?? 0;

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id') // Ambil akun tanpa parent
            ->whereNotIn('id', [101, 103, 104, 105, 201]) // Kecualikan ID tertentu
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray(); // Ubah menjadi array agar bisa digunakan di Blade

        // Ambil role dan bidang_name dari user yang sedang login
        $role = auth()->user()->role;
        $bidang_name = auth()->user()->bidang_name;

        // Tentukan prefix berdasarkan bidang_name
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_name) {
                case 'Kemasjidan':
                    $prefix = 'SJD-';
                    break;
                case 'Sosial':
                    $prefix = 'SOS-';
                    break;
                case 'Usaha':
                    $prefix = 'UHA-';
                    break;
                case 'Pembangunan':
                    $prefix = 'BGN-';
                    break;
                default:
                    $prefix = 'UNKNOWN-';
                    break;
            }
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . strtoupper($bidang_name) . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('transaksi.create', compact('akunTanpaParent', 'akunDenganParent', 'bidang_name', 'kodeTransaksi', 'lastSaldo'));
    }

    /***********************
     * Helper & Guards
     ***********************/

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

    protected function getSaldoLedgerSampaiTanggal(
        ?int $akunId,
        string $tanggal,
        ?int $bidangValue,
        string $userRole
    ): float {
        if (!$akunId)
            return 0.0;

        // Ambil akun untuk saldo_normal
        $akun = AkunKeuangan::find($akunId);
        if (!$akun)
            return 0.0;

        $q = Ledger::where('akun_keuangan_id', $akunId)
            ->whereHas('transaksi', function ($tr) use ($tanggal, $bidangValue, $userRole) {
                $tr->whereDate('tanggal_transaksi', '<=', $tanggal);

                // Bidang filter
                if ($userRole !== 'Bendahara') {
                    $tr->where('bidang_name', $bidangValue);
                } else {
                    $tr->whereNull('bidang_name');
                }
            });

        $debit = (float) $q->sum('debit');
        $credit = (float) $q->sum('credit');

        return $akun->saldo_normal === 'debit'
            ? ($debit - $credit)
            : ($credit - $debit);
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

    /***********************
     * Core Create Flow
     ***********************/
    public function storeTransaction(Request $request, $akun_keuangan_id, $parent_akun_id)
    {
        Log::info('Memulai proses penyimpanan transaksi', $request->all());

        $userRole = auth()->user()->role ?? 'Guest';

        // 1) Validasi
        $rules = [
            'kode_transaksi' => 'required|string|unique:transaksis,kode_transaksi',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];
        if ($userRole !== 'Bendahara') {
            $rules['bidang_name'] = 'required|integer';
        }
        $validated = $request->validate($rules);

        // 2) Konteks bidang
        $bidangValue = ($userRole === 'Bendahara') ? null : (int) $validated['bidang_name'];

        // 3) Casting aman
        $akun_keuangan_id = (int) $akun_keuangan_id;
        $parent_akun_id = $parent_akun_id ? (int) $parent_akun_id : null;
        $tanggal = Carbon::parse($validated['tanggal_transaksi'])->toDateString();
        $amount = (float) $validated['amount'];
        $tipe = $validated['type']; // 'penerimaan' | 'pengeluaran'

        // 4) Ambil saldo sebelumnya via KOLOM `saldo` (<= cutoff)
        $saldoSebelumnyaAkun = $this->getSaldoLedgerSampaiTanggal(
            $akun_keuangan_id,
            $tanggal,
            $bidangValue,
            $userRole
        );

        $saldoSebelumnyaLawan = $this->getSaldoLedgerSampaiTanggal(
            $parent_akun_id,
            $tanggal,
            $bidangValue,
            $userRole
        );

        Log::info("Saldo akun $akun_keuangan_id sebelum:", ['saldo' => $saldoSebelumnyaAkun]);
        Log::info("Saldo akun lawan " . ($parent_akun_id ?? 'NULL') . " sebelum:", ['saldo' => $saldoSebelumnyaLawan]);

        // 5) Validasi limit saldo untuk PENGELUARAN (akun utama/sumber form)
        if ($tipe === 'pengeluaran' && $amount > $saldoSebelumnyaAkun) {
            return back()->with('error', 'Jumlah pengeluaran tidak boleh melebihi saldo akun utama.');
        }

        // 6) Validasi PENERIMAAN: saldo SUMBER (parent) wajib cukup bila parent Kas/Bank
        if ($tipe === 'penerimaan' && $parent_akun_id) {
            if ($this->isKasBank($parent_akun_id)) {
                if ($amount > $saldoSebelumnyaLawan) {
                    return back()->with('error', 'Saldo sumber (Kas/Bank) tidak mencukupi untuk transfer.');
                }
            } elseif ($this->needsSaldoCheck($parent_akun_id) && $amount > $saldoSebelumnyaLawan) {
                return back()->with('error', 'Jumlah penerimaan tidak boleh melebihi saldo akun asal (lawan).');
            }
        }

        // 7) Hitung saldo baru
        $newSaldoAkun = ($tipe === 'penerimaan')
            ? $saldoSebelumnyaAkun + $amount
            : $saldoSebelumnyaAkun - $amount;

        $newSaldoLawan = null;
        if ($parent_akun_id) {
            $newSaldoLawan = ($tipe === 'penerimaan')
                ? $saldoSebelumnyaLawan - $amount   // uang keluar dari lawan
                : $saldoSebelumnyaLawan + $amount;  // uang masuk ke lawan
            if ($newSaldoLawan === null || is_nan($newSaldoLawan))
                $newSaldoLawan = 0.0;
        }

        // 8) Guard negatif untuk Kas/Bank (akun sumber pada PENGELUARAN)
        if ($tipe === 'pengeluaran' && $this->isKasBank($akun_keuangan_id) && $newSaldoAkun < 0) {
            return back()->with('error', 'Transaksi gagal! Saldo Kas/Bank tidak boleh negatif.');
        }

        // 9) Guard negatif untuk sisi LAWAN yang Kas/Bank (semua skenario)
        if ($parent_akun_id && $this->isKasBank($parent_akun_id) && $newSaldoLawan !== null && $newSaldoLawan < 0) {
            return back()->with('error', 'Transaksi gagal! Saldo Kas/Bank (sumber) tidak boleh negatif.');
        }

        // 10) Simpan atomik
        DB::transaction(function () use ($validated, $akun_keuangan_id, $parent_akun_id, $bidangValue, $tanggal, $amount, $tipe, $newSaldoAkun, $newSaldoLawan) {
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

            // Lawan (jika ada)
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

                // Ledger lawan
                Ledger::create([
                    'transaksi_id' => $trxLawan->id,
                    'akun_keuangan_id' => $trxLawan->akun_keuangan_id,
                    'debit' => $trxLawan->type === 'penerimaan' ? $amount : 0,
                    'credit' => $trxLawan->type === 'pengeluaran' ? $amount : 0,
                ]);
            }

            // Ledger utama
            Ledger::create([
                'transaksi_id' => $trxAkun->id,
                'akun_keuangan_id' => $trxAkun->akun_keuangan_id,
                'debit' => $trxAkun->type === 'penerimaan' ? $amount : 0,
                'credit' => $trxAkun->type === 'pengeluaran' ? $amount : 0,
            ]);

            Log::info('Data transaksi berhasil disimpan', [
                'transaksi_utama_id' => $trxAkun->id,
            ]);
        });

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    /***********************
     * Entry Points
     ***********************/
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::error('User tidak terautentikasi.');
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        $role = $user->role;
        $bidang_id = $user->bidang_name; // integer id bidang

        // Tentukan akun kas default
        $akun_keuangan_id = $this->getDefaultKasAkunId($role, is_numeric($bidang_id) ? (int) $bidang_id : null);
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun keuangan (Kas) tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan akun ada
        if (!DB::table('akun_keuangans')->where('id', $akun_keuangan_id)->exists()) {
            return back()->withErrors(['error' => "Akun keuangan ID $akun_keuangan_id tidak ditemukan."]);
        }

        // Validasi parent_akun_id jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        // Wajib akun lawan untuk penerimaan ke kas
        if ($request->input('type') === 'penerimaan' && empty($parent_akun_id)) {
            return back()->withErrors(['parent_akun_id' => 'Akun lawan wajib diisi untuk transaksi penerimaan.']);
        }

        // Merge konteks agar validasi & saldo konsisten
        $request->merge([
            'bidang_name' => $role === 'Bendahara' ? null : $bidang_id,
            'akun_keuangan_id' => $akun_keuangan_id,
        ]);

        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function storeBankTransaction(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::error('User tidak terautentikasi.');
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        $role = $user->role;
        $bidang_id = $user->bidang_name;

        // Tentukan akun bank default
        $akun_keuangan_id = $this->getDefaultBankAkunId($role, is_numeric($bidang_id) ? (int) $bidang_id : null);
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Validasi parent_akun_id jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        // Wajib akun lawan untuk penerimaan ke bank
        if ($request->input('type') === 'penerimaan' && empty($parent_akun_id)) {
            return back()->withErrors(['parent_akun_id' => 'Akun lawan wajib diisi untuk transaksi penerimaan.']);
        }

        // Merge konteks
        $request->merge([
            'bidang_name' => $role === 'Bendahara' ? null : $bidang_id,
            'akun_keuangan_id' => $akun_keuangan_id,
        ]);

        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function edit($id)
    {
        $transaksi = Transaksi::findOrFail($id);

        // Parent akun dari parent_akun_id (tetap seperti semula)
        $akunKeuangan = AkunKeuangan::find($transaksi->parent_akun_id)?->parent_id ?? $transaksi->parent_akun_id;

        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->whereNotIn('id', [103, 104, 105]) // sesuaikan pengecualian
            ->get();

        $oldParentAkunId = old('parent_akun_id', $transaksi->parent_akun_id ?? null);

        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->orderByRaw("FIELD(id, ?) DESC", [$oldParentAkunId])
            ->get()
            ->groupBy('parent_id');

        return view('transaksi.edit', compact('transaksi', 'akunKeuangan', 'akunTanpaParent', 'oldParentAkunId', 'akunDenganParent'));
    }

    public function update(Request $request, $id)
    {
        // Logging awal untuk debugging
        Log::info('🚀 Masuk ke updateKasTransaction', ['id' => $id, 'request' => $request->all()]);

        // Validasi data input
        try {
            $validatedData = $request->validate([
                'bidang_name' => 'required|integer', // Pastikan bidang_name adalah integer
                'kode_transaksi' => 'required|string',
                'tanggal_transaksi' => 'required|date',
                'type' => 'required|in:penerimaan,pengeluaran',
                'akun_keuangan_id' => 'required|integer',
                'parent_akun_id' => 'nullable|integer',
                'deskripsi' => 'required|string',
                'amount' => 'required|numeric|min:0',
            ]);
            Log::info('✅ Validasi berhasil', ['validatedData' => $validatedData]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validasi gagal', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors());
        }

        // Cari transaksi berdasarkan ID
        $transaksi = Transaksi::find($id);
        if (!$transaksi) {
            Log::error('❌ Transaksi tidak ditemukan', ['id' => $id]);
            return back()->withErrors(['error' => 'Transaksi tidak ditemukan.']);
        }
        Log::info('✅ Transaksi ditemukan', ['transaksi' => $transaksi]);

        // Ambil user yang sedang login
        $user = auth()->user();

        // Jika pengguna adalah Bendahara, gunakan akun keuangan khusus
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011;
        } else {
            // Mapping akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];
            $akun_keuangan_id = $akunKas[$validatedData['bidang_name']] ?? null;
        }

        // Pastikan akun_keuangan_id ditemukan
        if (!$akun_keuangan_id) {
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun keuangan.']);
        }

        // Ambil transaksi yang akan diperbarui
        $transaksi = Transaksi::findOrFail($id);

        // Ambil saldo terakhir sebelum transaksi ini
        $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
            ->where('bidang_name', $validatedData['bidang_name'])
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0;
        Log::info('🔄 Saldo akun Kas sebelum update', ['saldoKas' => $saldoKas]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoKas) {
            Log::warning('⚠️ Pengeluaran melebihi saldo', [
                'amount' => $validatedData['amount'],
                'saldoKas' => $saldoKas
            ]);
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Kas.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoKas;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi dalam database
        try {
            $transaksi->update([
                'bidang_name' => $validatedData['bidang_name'], // Tetap menggunakan bidang_name
                'kode_transaksi' => $validatedData['kode_transaksi'],
                'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
                'type' => $validatedData['type'],
                'akun_keuangan_id' => $akun_keuangan_id, // Pastikan ini untuk akun Kas
                'parent_akun_id' => $validatedData['parent_akun_id'],
                'deskripsi' => $validatedData['deskripsi'],
                'amount' => $validatedData['amount'],
                'saldo' => $newSaldo,
            ]);
            Log::info('✅ Data transaksi Kas berhasil diperbarui', ['id' => $transaksi->id, 'saldo_baru' => $newSaldo]);
        } catch (\Exception $e) {
            Log::error('❌ Gagal update transaksi', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal update transaksi.']);
        }

        // Perbarui jurnal berdasarkan metode pembayaran (Kas)
        try {
            $this->createJournalEntry($transaksi);
            Log::info('✅ Jurnal transaksi berhasil diperbarui', ['id' => $transaksi->id]);
        } catch (\Exception $e) {
            Log::error('❌ Gagal update jurnal transaksi', ['error' => $e->getMessage()]);
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Kas berhasil diperbarui!');
    }

    public function storeOpeningBalance(Request $request)
    {
        $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'kode_transaksi' => ['nullable', 'string', 'max:255'],
            'kas_bank_akun_id' => ['required', 'exists:akun_keuangans,id'],
            'akun_keuangan_id' => ['required', 'exists:akun_keuangans,id'], // Induk Aset Neto
            'parent_akun_id' => ['nullable', 'exists:akun_keuangans,id'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $tanggal = Carbon::parse($request->tanggal_transaksi);
        $amount = (float) $request->amount;
        $deskripsi = $request->deskripsi ?: 'Saldo awal per ' . $tanggal->translatedFormat('d F Y');

        $kasBankId = (int) $request->kas_bank_akun_id;
        $asetNetoId = $request->parent_akun_id ?: $request->akun_keuangan_id;

        DB::transaction(function () use ($request, $tanggal, $amount, $deskripsi, $kasBankId, $asetNetoId) {
            $kodeTransaksi = $request->filled('kode_transaksi')
                ? $request->kode_transaksi
                : 'OPEN-' . $tanggal->format('Y') . '-' . strtoupper(uniqid());

            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_transaksi' => $tanggal->toDateString(),
                'type' => 'penerimaan', // hanya isi kolom
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $kasBankId,
                'parent_akun_id' => $asetNetoId,
                'bidang_name' => auth()->user()->role === 'Bidang'
                    ? auth()->user()->bidang_name
                    : null,
                'sumber' => null,
                'amount' => $amount,
                'saldo' => 0,
            ]);

            // Debit Kas/Bank
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kasBankId,
                'debit' => $amount,
                'credit' => 0,
            ]);

            // Kredit Aset Neto
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $asetNetoId,
                'debit' => 0,
                'credit' => $amount,
            ]);
        });

        return back()->with('success', 'Saldo awal (opening balance) berhasil dicatat.');
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'kode_transaksi' => 'required|string|max:255',
            'tanggal_transaksi' => 'required|date',
            'sumber_akun_id' => 'required|exists:akun_keuangans,id',
            'tujuan_akun_id' => 'required|exists:akun_keuangans,id|different:sumber_akun_id',
            'amount' => 'required|numeric|min:1',
            'deskripsi' => 'required|string|max:255',
        ]);

        $amount = (float) $request->amount;
        $sumberId = (int) $request->sumber_akun_id;
        $tujuanId = (int) $request->tujuan_akun_id;
        $kode = $request->kode_transaksi;
        $tanggal = Carbon::parse($request->tanggal_transaksi)->toDateString();
        $deskripsi = $request->deskripsi;

        $user = auth()->user();
        $bidangName = $user->role === 'Bidang' ? $user->bidang_name : null;

        DB::transaction(function () use ($amount, $sumberId, $tujuanId, $kode, $tanggal, $deskripsi, $bidangName) {
            // Pastikan akun ada (sekalian bisa dipakai untuk logging kalau perlu)
            $akunSumber = AkunKeuangan::findOrFail($sumberId);
            $akunTujuan = AkunKeuangan::findOrFail($tujuanId);

            // ================================
            // 🔹 1) TRANSAKSI SUMBER (KREDIT)
            //     type = pengeluaran
            //     parent_akun_id = ID akun tujuan
            // ================================
            $trxSumber = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengeluaran',
                'akun_keuangan_id' => $sumberId,
                'parent_akun_id' => $tujuanId,   // ⬅ tujuan kas/bank
                'deskripsi' => $deskripsi,
                'amount' => $amount,
                'saldo' => 0,
                'bidang_name' => $bidangName,
                'sumber' => 'transfer',
            ]);

            Ledger::create([
                'transaksi_id' => $trxSumber->id,
                'akun_keuangan_id' => $sumberId,
                'debit' => 0,
                'credit' => $amount,
            ]);

            // ================================
            // 🔹 2) TRANSAKSI TUJUAN (DEBIT)
            //     type = penerimaan
            //     parent_akun_id = ID akun tujuan (konsisten)
            // ================================
            $trxTujuan = Transaksi::create([
                'kode_transaksi' => $kode .'-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'penerimaan',
                'akun_keuangan_id' => $tujuanId,
                'parent_akun_id' => $tujuanId,   // ⬅ tetap akun tujuan
                'deskripsi' => $deskripsi,
                'amount' => $amount,
                'saldo' => 0,
                'bidang_name' => $bidangName,
                'sumber' => 'transfer',
            ]);

            Ledger::create([
                'transaksi_id' => $trxTujuan->id,
                'akun_keuangan_id' => $tujuanId,
                'debit' => $amount,
                'credit' => 0,
            ]);
        });

        return back()->with('success', 'Transfer antar akun berhasil!');
    }


    public function updateBankTransaction(Request $request, $id)
    {
        // Logging untuk debugging
        Log::info('Memulai proses update transaksi Bank', ['id' => $id, 'request' => $request->all()]);

        // Validasi data input
        $validatedData = $request->validate([
            'bidang_name' => 'required|integer|exists:bidangs,id', // bidang_name harus berupa integer dan ada di tabel bidangs
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'akun_keuangan_id' => 'required|integer',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // Pastikan akun yang digunakan termasuk akunBank dengan prefix 102
        $akunBankList = [1021, 1022, 1023, 1024, 1025]; // Menggunakan prefix 102
        if (!in_array($validatedData['akun_keuangan_id'], $akunBankList)) {
            return back()->withErrors(['akun_keuangan_id' => 'Akun yang dipilih bukan akunBank yang valid.']);
        }

        // Ambil transaksi yang akan diperbarui
        $transaksi = Transaksi::findOrFail($id);

        // Ambil saldo terakhir sebelum transaksi ini untuk akunBank terkait
        $lastSaldo = Transaksi::where('akun_keuangan_id', $validatedData['akun_keuangan_id'])
            ->where('bidang_name', $validatedData['bidang_name']) // Sesuai bidang_name (integer)
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoBank = $lastSaldo ? $lastSaldo->saldo : 0;

        Log::info('Saldo akunBank sebelum update:', ['akun_keuangan_id' => $validatedData['akun_keuangan_id'], 'saldo' => $saldoBank]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoBank) {
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akunBank.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoBank;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi dengan akunBank yang valid
        $transaksi->update([
            'bidang_name' => $validatedData['bidang_name'], // Menggunakan bidang_name yang berupa integer
            'kode_transaksi' => $validatedData['kode_transaksi'],
            'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
            'type' => $validatedData['type'],
            'akun_keuangan_id' => $validatedData['akun_keuangan_id'], // Sesuai dengan akunBank yang dipilih
            'parent_akun_id' => $validatedData['parent_akun_id'],
            'deskripsi' => $validatedData['deskripsi'],
            'amount' => $validatedData['amount'],
            'saldo' => $newSaldo,
        ]);

        Log::info('Data transaksi Bank berhasil diperbarui', ['id' => $transaksi->id]);

        // Perbarui jurnal khusus untuk akunBank
        $this->createBankJournalEntry($transaksi);

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Bank berhasil diperbarui!');
    }

    public function getData()
    {
        $user = auth()->user();

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user') // Pastikan relasi sudah dimuat
            ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan

        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        // Debugging relasi parentAkunKeuangan
        Log::info('Data transaksi dengan parent akun:', $transaksi->toArray());

        return DataTables::of($transaksi)
            ->addColumn('parent_akun_nama', function ($item) {
                return $item->parentAkunKeuangan ? $item->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('actions', function ($item) {
                return view('transaksi.actions', ['id' => $item->id]);
            })
            ->rawColumns(['actions']) // Memberikan raw HTML pada kolom actions
            ->make(true);
    }

    private function createJournalEntry($transaksi)
    {
        // Ambil user yang sedang login
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id

        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1021;
        } else {
            $akunBank = [
                1 => 1022,
                2 => 1023,
                3 => 1024,
                4 => 1025,
            ];
            $akun_keuangan_id = $akunBank[$bidang_id] ?? null;
        }
        // Pastikan akun_keuangan_id ditemukan
        if (!$akun_keuangan_id) {
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun keuangan.']);
        }

        // Ambil akun Kas dan Pendapatan
        $kas = AkunKeuangan::where('id', $akun_keuangan_id)->first(); // Akun Kas
        $pendapatan = AkunKeuangan::where('id', 202)->first(); // Akun Pendapatan

        // Tentukan akun beban berdasarkan parent_akun_id
        $akunBebanId = $transaksi->parent_akun_id ?? $transaksi->akun_keuangan_id;

        if (!$kas || !$pendapatan || !$akunBebanId) {
            Log::error('Akun tidak ditemukan dalam database', [
                'kas' => $kas,
                'pendapatan' => $pendapatan,
                'akunBebanId' => $akunBebanId,
            ]);
            return;
        }

        // Hapus ledger lama untuk transaksi ini agar tidak duplikat
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Jika transaksi adalah penerimaan
        if ($transaksi->type == 'penerimaan') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id,
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $pendapatan->id,
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }
        // Jika transaksi adalah pengeluaran
        else if ($transaksi->type == 'pengeluaran') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunBebanId,
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id,
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }

        // Hitung total saldo kas setelah transaksi
        $totalKas = Ledger::where('akun_keuangan_id', $kas->id)
            ->sum('debit') - Ledger::where('akun_keuangan_id', $kas->id)->sum('credit');

        Log::info('Jurnal berhasil diperbarui. Saldo kas terbaru: ' . $totalKas, [
            'id_transaksi' => $transaksi->id,
            'total_kas' => $totalKas
        ]);
    }

    private function createBankJournalEntry($transaksi)
    {
        // Ambil akun Bank dan Pendapatan
        $bank = AkunKeuangan::where('id', 102)->first(); // Akun Bank
        $pendapatan = AkunKeuangan::where('id', 202)->first(); // Akun Pendapatan

        // Tentukan akun beban berdasarkan parent_akun_id
        $akunBebanId = $transaksi->parent_akun_id ?? $transaksi->akun_keuangan_id;

        if (!$bank || !$pendapatan || !$akunBebanId) {
            Log::error('Akun tidak ditemukan dalam database', [
                'bank' => $bank,
                'pendapatan' => $pendapatan,
                'akunBebanId' => $akunBebanId,
            ]);
            return;
        }

        // Hapus ledger lama untuk transaksi ini agar tidak duplikat
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Cari entri ledger berdasarkan transaksi ID
        $ledgerBank = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $bank->id)
            ->first();

        $ledgerPendapatan = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $pendapatan->id)
            ->first();

        $ledgerBeban = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $akunBebanId)
            ->first();

        // Jika transaksi adalah penerimaan
        if ($transaksi->type == 'penerimaan') {
            if ($ledgerBank && $ledgerPendapatan) {
                // Update nilai jika sudah ada jurnal sebelumnya
                $ledgerBank->update(['debit' => $transaksi->amount, 'credit' => 0]);
                $ledgerPendapatan->update(['credit' => $transaksi->amount, 'debit' => 0]);
            } else {
                // Jika belum ada, buat jurnal baru
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $bank->id,
                    'debit' => $transaksi->amount,
                    'credit' => 0
                ]);

                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $pendapatan->id,
                    'debit' => 0,
                    'credit' => $transaksi->amount
                ]);
            }
        }
        // Jika transaksi adalah pengeluaran
        else if ($transaksi->type == 'pengeluaran') {
            if ($ledgerBank && $ledgerBeban) {
                // Update jika sudah ada
                $ledgerBank->update(['credit' => $transaksi->amount, 'debit' => 0]);
                $ledgerBeban->update(['debit' => $transaksi->amount, 'credit' => 0]);
            } else {
                // Buat jurnal baru jika belum ada
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $akunBebanId,
                    'debit' => $transaksi->amount,
                    'credit' => 0
                ]);

                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $bank->id,
                    'debit' => 0,
                    'credit' => $transaksi->amount
                ]);
            }
        }

        // Hitung total saldo bank setelah transaksi
        $totalBank = Ledger::where('akun_keuangan_id', $bank->id)
            ->sum('debit') - Ledger::where('akun_keuangan_id', $bank->id)->sum('credit');

        Log::info('Jurnal bank berhasil diperbarui. Saldo bank terbaru: ' . $totalBank, [
            'id_transaksi' => $transaksi->id,
            'total_bank' => $totalBank
        ]);
    }

    public function destroy($id)
    {
        // Ambil transaksi utama
        $transaksi = Transaksi::findOrFail($id);

        // Cek dan ambil transaksi lawan berdasarkan pola kode_transaksi
        $kodeLawan = $transaksi->kode_transaksi . '-LAWAN';
        $transaksiLawan = Transaksi::where('kode_transaksi', $kodeLawan)->first();

        // Hapus ledger untuk transaksi utama
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Hapus ledger untuk transaksi lawan jika ada
        if ($transaksiLawan) {
            Ledger::where('transaksi_id', $transaksiLawan->id)->delete();
            $transaksiLawan->delete(); // Hapus transaksi lawan
        }

        // Hapus transaksi utama
        $transaksi->delete();

        return redirect()->route('transaksi.index.bidang')->with('success', 'Transaksi dan data terkait berhasil dihapus.');
    }

    public function exportNota($id)
    {
        // Retrieve the transaction data based on ID
        $transaksi = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])->find($id);

        // Pastikan $transaksi ditemukan sebelum melanjutkan
        if (!$transaksi) {
            return redirect()->route('transaksi.index')->with('error', 'Transaksi tidak ditemukan');
        }

        // Mengambil data yang diperlukan dari transaksi
        $tanggal_transaksi = $transaksi->tanggal_transaksi;
        $jenis_transaksi = $transaksi->type;
        $akun = $transaksi->akunKeuangan ? $transaksi->akunKeuangan->nama_akun : 'N/A';
        $sub_akun = $transaksi->parentAkunKeuangan ? $transaksi->parentAkunKeuangan->nama_akun : 'N/A';

        // Generate the PDF from a view
        $pdf = Pdf::loadView('transaksi.nota', compact('transaksi', 'tanggal_transaksi', 'akun', 'sub_akun', 'jenis_transaksi'));

        // Return the PDF as a response for download
        return $pdf->download('Invoice_' . $transaksi->kode_transaksi . '.pdf');
    }

    public function exportAllPdf()
    {
        // Ambil user yang sedang login
        $user = auth()->user();

        // Query transaksi berdasarkan role dan bidang_name
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user')
            ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan

        // Filter berdasarkan role 'Bidang'
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        // Ambil hasil query
        $transaksi = $transaksiQuery->get();

        // Pastikan ada data transaksi yang tersedia
        if ($transaksi->isEmpty()) {
            return redirect()->route('transaksi.index')->with('error', 'Tidak ada data transaksi untuk diunduh!.');
        }

        // Siapkan data untuk dikirim ke view PDF
        $data = [
            'transaksis' => $transaksi
        ];

        // Ambil bidang_name dari user login sebagai nama file PDF
        $bidangName = $user->bidang_name ?? 'Transaksi'; // Gunakan bidang_name user atau default 'Transaksi'

        // Load view untuk PDF, kirimkan data transaksi
        $pdf = Pdf::loadView('transaksi.export', $data);

        // Kembalikan file PDF untuk di-download
        return $pdf->download('Laporan_Keuangan_' . $bidangName . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        $bidangName = $request->input('bidang_name');
        if ($user->role === 'Bidang') {
            $bidangName = $user->bidang_name;
        }

        $bulan = $request->input('bulan'); // format YYYY-MM
        $startDate = $endDate = null;

        if ($bulan) {
            [$year, $month] = explode('-', $bulan);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = (clone $startDate)->endOfMonth()->endOfDay();
        }

        // ⬇️ Pakai scope yang sudah include:
        //    - buang internal kas/bank
        //    - buang kode_transaksi "-LAWAN"
        $query = Transaksi::query()
            ->excludeInternalKasBankAndLawan();

        if ($bidangName) {
            $query->where('bidang_name', $bidangName);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
        }

        if (!$query->exists()) {
            return back()->with('error', 'Tidak ada transaksi sesuai filter.');
        }

        $fileName = 'Buku_Harian';
        if ($bidangName)
            $fileName .= '_Bidang_' . $bidangName;
        if ($bulan)
            $fileName .= '_' . $bulan;
        $fileName .= '.xlsx';

        return Excel::download(
            new BukuHarianExport($bidangName, $startDate, $endDate),
            $fileName
        );
    }

}
