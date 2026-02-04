<?php

namespace App\Http\Controllers\Sosial;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Models\Hutang;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\HasTransaksiKasBank;
use App\Services\LaporanKeuanganService;

class DanaKematianController extends Controller
{
    use HasTransaksiKasBank;

    // 1 akun liabilitas untuk Dana Kematian
    private const AKUN_HUTANG_DK = 5005;

    // akun beban
    private const AKUN_BEBAN_SANTUNAN   = 3039; // Biaya Pemakaman (Beban Terikat)
    private const AKUN_BEBAN_OPERASIONAL = 3085; // Pengeluaran Pemakaman (Beban tidak terikat)

    // marker hutang warga dana kematian
    private const DK_MARKER = '[DK]';

    // marker pool
    private const DK_POOL_KODE = 'DK-POOL';
    private const DK_POOL_DESC = '[DK-POOL] Dana Kematian Pool';

    public function __construct()
    {
        $this->middleware(['auth']);

        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user?->bidang || $user->bidang->name !== 'Sosial') {
                abort(403, 'Akses khusus Bidang Sosial');
            }
            return $next($request);
        });
    }

    protected function bidangSosialKey(): int
    {
        $user = auth()->user();
        return is_numeric($user->bidang_name ?? null) ? (int)$user->bidang_name : 3;
    }

    protected function getSaldoDanaKematianPool(): float
    {
        return (float) LaporanKeuanganService::getSaldoAkun(
            self::AKUN_HUTANG_DK,
            $this->bidangSosialKey(),
            null,          // startDate null = all-time sampai endDate
            now(),         // endDate
            true           // exclude -LAWAN (opsional, sesuai desain Anda)
        );
    }

    // =========================
    // POOL (Dana Kematian)
    // =========================
    protected function getOrCreateDanaKematianPool(): Hutang
    {
        // POOL harus unik dan tidak dobel:
        // akun=5005, warga_kepala_id NULL, kode_transaksi DK-POOL
        return Hutang::firstOrCreate(
            [
                'akun_keuangan_id' => self::AKUN_HUTANG_DK,
                'warga_kepala_id'  => null,
                'kode_transaksi'   => self::DK_POOL_KODE,
            ],
            [
                'user_id'             => auth()->id(),
                'parent_id'           => null,
                'iuran_bulanan_id'    => null,
                'jumlah'              => 0,
                'tanggal_jatuh_tempo' => now()->addYears(50)->toDateString(),
                'deskripsi'           => self::DK_POOL_DESC,
                'status'              => 'belum_lunas',
                'bidang_name'         => $this->bidangSosialKey(), // optional (informasi), bukan key
            ]
        );
    }


    protected function getSaldoHutangWargaDanaKematian(int $wargaId): float
    {
        return (float) Hutang::query()
            ->where('akun_keuangan_id', self::AKUN_HUTANG_DK)
            ->where('warga_kepala_id', $wargaId)        // saldo per warga
            ->where('status', 'belum_lunas')
            ->where('deskripsi', 'like', '[DK]%')       // marker transaksi DK warga
            ->sum('jumlah');
    }


    // =========================
    // Halaman Pengeluaran (tab)
    // =========================
    public function pengeluaran()
    {
        $this->getOrCreateDanaKematianPool();

        $wargas = Warga::query()
            ->kepalaKeluarga()
            ->orderBy('nama')
            ->get(['id', 'nama', 'hp', 'rt', 'alamat']);

        $saldoPool = $this->getSaldoDanaKematianPool();

        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'revenue', 'expense', 'liability'])
            ->get();

        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'revenue', 'expense', 'liability'])
            ->get()
            ->groupBy('parent_id');


        return view('bidang.sosial.dana-kematian.pengeluaran', compact('wargas', 'saldoPool', 'akunTanpaParent', 'akunDenganParent'));
    }

    // =========================
    // AJAX saldo hutang DK warga
    // =========================
    public function getSaldoHutangDanaKematian(Request $request, Warga $warga)
    {
        $saldo = $this->getSaldoHutangWargaDanaKematian($warga->id);

        return response()->json([
            'warga_id' => $warga->id,
            'saldo' => (float) $saldo,
            'saldo_fmt' => number_format((float) $saldo, 0, ',', '.'),
        ]);
    }

    public function getSaldoKasBankByMetode(Request $request, string $metode)
    {
        abort_unless(in_array($metode, ['tunai', 'transfer'], true), 404);

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'akun_id' => null,
                'saldo' => 0,
                'saldo_fmt' => '0',
                'error' => 'User tidak terautentikasi.',
            ], 401);
        }

        $bidangId = $this->bidangSosialKey(); // FIX: ini yang benar (int 3)

        // Resolver akun kas/bank berdasarkan metode (tunai/transfer)
        $akunKasBankId = $this->resolveAkunPenerimaanByMetode(
            $user->role,
            $bidangId,
            $metode
        );

        if (!$akunKasBankId) {
            return response()->json([
                'akun_id' => null,
                'saldo' => 0,
                'saldo_fmt' => '0',
                'error' => 'Akun kas/bank belum dikonfigurasi untuk metode ini.',
            ], 422);
        }

        // Ambil saldo kas/bank dari ledger via service
        // Pastikan getSaldoAkun() memang PUBLIC. Kalau masih protected, lihat bagian C di bawah.
        $saldo = (float) LaporanKeuanganService::getSaldoAkun((int) $akunKasBankId, $bidangId);

        $akunNama = AkunKeuangan::where('id', (int) $akunKasBankId)->value('nama_akun');

        return response()->json([
            'akun_id'   => (int) $akunKasBankId,
            'akun_nama' => (string) ($akunNama ?? ''),
            'saldo'     => $saldo,
            'saldo_fmt' => number_format($saldo, 0, ',', '.'),
        ]);
    }

    protected function getSaldoHutangWarga(int $wargaId): float
    {
        return (float) Hutang::query()
            ->where('akun_keuangan_id', self::AKUN_HUTANG_DK)
            ->where('warga_kepala_id', $wargaId)
            ->where('status', 'belum_lunas')
            ->where('deskripsi', 'like', self::DK_MARKER . '%')
            ->sum('jumlah');
    }

    // =========================
    // FIFO pengurangan hutang warga
    // =========================
    protected function kurangiHutangWargaFIFO(int $wargaId, float $amount): void
    {
        $sisa = $amount;

        $hutangs = Hutang::query()
            ->where('akun_keuangan_id', self::AKUN_HUTANG_DK)
            ->where('warga_kepala_id', $wargaId)
            ->where('status', 'belum_lunas')
            ->where('deskripsi', 'like', self::DK_MARKER . '%')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($hutangs as $h) {
            if ($sisa <= 0) break;

            $saldoBaris = (float) $h->jumlah;
            $pakai = min($saldoBaris, $sisa);

            $h->jumlah = $saldoBaris - $pakai;

            if ((float) $h->jumlah <= 0) {
                $h->jumlah = 0;
                $h->status = 'lunas';
            }

            $h->save();
            $sisa -= $pakai;
        }

        if ($sisa > 0.00001) {
            throw new \Exception('Saldo hutang warga tidak mencukupi untuk santunan.');
        }
    }

    // =========================
    // STORE Pengeluaran (Santunan / Operasional)
    // =========================
    public function storePengeluaranDanaKematian(Request $request, string $jenis)
    {
        abort_unless(in_array($jenis, ['santunan', 'operasional'], true), 404);

        $request->validate([
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:1'],
            'metode_bayar' => ['required', Rule::in(['tunai', 'transfer'])],
            'deskripsi' => ['required', 'string', 'max:255'],

            // optional: pilih mode jurnal (beban / pool)
            // kalau belum ada di form, aman karena kita set default di bawah
            'mode_jurnal' => ['nullable', Rule::in(['beban', 'pool'])],

            'warga_kepala_id' => [
                Rule::requiredIf($jenis === 'santunan'),
                'nullable',
                'exists:wargas,id',
            ],
            'akun_keuangan_id' => [
                Rule::requiredIf($jenis === 'operasional'),
                'nullable',
                'integer',
                'exists:akun_keuangans,id',
            ],
            'parent_akun_id' => [
                'nullable',
                'integer',
                'exists:akun_keuangans,id',
            ],
        ]);

        return DB::transaction(function () use ($request, $jenis) {
            $user = auth()->user();
            $amount = (float) $request->amount;

            $bidangId = $this->bidangSosialKey();

            $akunKasBank = $this->resolveAkunPenerimaanByMetode(
                $user->role,
                $bidangId,
                $request->metode_bayar
            );

            $akunHutang = self::AKUN_HUTANG_DK; // 5005 (pool/liability)

            /**
             * =========================
             * Tentukan MODE jurnal
             * =========================
             * RULE FIX:
             * - Operasional => BEBAN
             * - Pool/liability => 5005
             * - Tidak boleh dua-duanya
             */
            $mode = $request->filled('mode_jurnal')
                ? $request->mode_jurnal
                : ($jenis === 'operasional' ? 'beban' : 'pool'); // default bisa kamu ubah

            // ===== Validasi saldo tergantung mode =====
            if ($mode === 'pool') {
                $saldoPool = $this->getSaldoDanaKematianPool();
                if ($amount > $saldoPool) {
                    throw new \Exception('Saldo Dana Kematian (POOL) tidak mencukupi.');
                }
            }

            if ($jenis === 'santunan') {
                $wargaId = (int) $request->warga_kepala_id;
                $saldoWarga = $this->getSaldoHutangWargaDanaKematian($wargaId);

                if ($amount > $saldoWarga) {
                    throw new \Exception('Saldo hutang warga tidak mencukupi untuk santunan.');
                }
            }

            // ===== Tentukan akun lawan (debit) =====
            $akunDebit = null;

            if ($mode === 'pool') {
                // Mode POOL => debit 5005
                $akunDebit = $akunHutang;
            } else {
                // Mode BEBAN => debit akun beban (santunan atau operasional)
                if ($jenis === 'santunan') {
                    $akunDebit = self::AKUN_BEBAN_SANTUNAN;
                } else {
                    $selectedParentId = (int) $request->akun_keuangan_id;
                    $selectedChildId  = $request->filled('parent_akun_id') ? (int) $request->parent_akun_id : null;

                    if ($selectedChildId) {
                        $akunDebit = AkunKeuangan::query()
                            ->where('id', $selectedChildId)
                            ->where('tipe_akun', 'expense')
                            ->where('parent_id', $selectedParentId)
                            ->value('id');

                        if (!$akunDebit) {
                            throw new \Exception('Sub Akun tidak valid atau bukan BEBAN (expense).');
                        }
                    } else {
                        $akunDebit = AkunKeuangan::query()
                            ->where('id', $selectedParentId)
                            ->where('tipe_akun', 'expense')
                            ->value('id');

                        if (!$akunDebit) {
                            throw new \Exception('Asal Akun Operasional harus akun BEBAN (expense).');
                        }
                    }
                }
            }

            // buat kode transaksi unik
            $kode = 'DK-' . strtoupper($jenis) . '-' . now()->format('YmdHis');

            // parent_akun_id harus konsisten dengan akun lawan yang dipakai
            $trx = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'type' => 'pengeluaran',
                'akun_keuangan_id' => $akunKasBank,
                'parent_akun_id' => $akunDebit, // ✅ akun lawan real (beban ATAU 5005)
                'deskripsi' => $request->deskripsi,
                'amount' => $amount,
                'bidang_name' => (string) ($user->bidang_name ?? '3'),
                'user_id' => $user->id,
            ]);

            /**
             * =========================
             * LEDGER (HANYA 2 BARIS)
             * =========================
             * Cr Bank
             * Dr akunDebit (Beban ATAU 5005)
             */
            Ledger::create([
                'transaksi_id' => $trx->id,
                'akun_keuangan_id' => $akunKasBank,
                'debit' => 0,
                'credit' => $amount,
            ]);

            Ledger::create([
                'transaksi_id' => $trx->id,
                'akun_keuangan_id' => $akunDebit,
                'debit' => $amount,
                'credit' => 0,
            ]);

            // =========================
            // Sub-ledger / update pool  
            // =========================
            if ($jenis === 'santunan') {
                // hutang warga selalu berkurang (sub-ledger FIFO)
                $this->kurangiHutangWargaFIFO((int) $request->warga_kepala_id, $amount);
            }

            // Pool hanya berkurang bila mode = pool (karena itu memang liability pool)
            if ($mode === 'pool') {
                $pool = $this->getOrCreateDanaKematianPool();
                $saldoPool = $this->getSaldoDanaKematianPool();
                $pool->jumlah = $saldoPool - $amount;
                $pool->save();
            }

            return back()->with('success', 'Pengeluaran Dana Kematian berhasil dicatat.');
        });
    }
}
