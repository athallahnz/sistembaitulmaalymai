<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Exports\LaporanExport;
use App\Models\AkunKeuangan;
use App\Models\Ledger;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;


class LaporanKeuanganController extends Controller
{
    /*********************************************************
     *  HELPER: Mapping Kas / Bank per User (Role & Bidang)
     *********************************************************/
    private function resolveKasBankForUser(): array
    {
        $user = auth()->user();
        $role = $user->role ?? 'Guest';
        $bidangId = $user->bidang_name ?? null;   // integer id bidang (1..4)

        // catatan: akun_keuangan_id = kode_akun (1011, 1012, dst.)
        $kasMap = [1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015];
        $bankMap = [1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025];

        if ($role === 'Bendahara') {
            return [
                'role' => 'Bendahara',
                'bidangId' => null,
                'kasId' => 1011,
                'bankId' => 1021,
            ];
        }

        return [
            'role' => 'Bidang',
            'bidangId' => $bidangId,
            'kasId' => $kasMap[$bidangId] ?? null,
            'bankId' => $bankMap[$bidangId] ?? null,
        ];
    }

    /*********************************************************
     *  HELPER: Hitung saldo 1 akun dari aggregate ledger
     *********************************************************/
    private function hitungSaldoAkun(AkunKeuangan $akun, ?object $agg): float
    {
        if (!$agg) {
            return 0.0;
        }

        $totalDebit = (float) $agg->total_debit;
        $totalKredit = (float) $agg->total_credit;

        return $akun->saldo_normal === 'debit'
            ? ($totalDebit - $totalKredit)
            : ($totalKredit - $totalDebit);
    }

    /*********************************************************
     *  ========== 1) LAPORAN ARUS KAS (PSAK 2) ==========
     *********************************************************/

    /**
     * Core builder Arus Kas (dipakai web + export).
     */
    private function buildArusKasData(Carbon $startDate, Carbon $endDate): array
    {
        $map = $this->resolveKasBankForUser();
        $role = $map['role'];       // 'Bidang' atau 'Bendahara'
        $bidangId = $map['bidangId'];

        // Semua akun kas/bank
        $kasBankIds = AkunKeuangan::where('is_kas_bank', true)
            ->pluck('id')
            ->toArray();

        // Ledger yang menyentuh kas/bank di periode tsb
        $cashLedgers = Ledger::with([
            'transaksi',
            'transaksi.akunKeuangan',
            'transaksi.parentAkunKeuangan',
            'akun',
        ])
            ->whereIn('akun_keuangan_id', $kasBankIds)
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate, $role, $bidangId) {
                $q->whereBetween('tanggal_transaksi', [$startDate, $endDate]);

                if (!($role === 'Bendahara')) {
                    $q->where('bidang_name', $bidangId);
                }
            })
            ->get()
            ->groupBy('transaksi_id');

        Log::info('DEBUG ARUS KAS', [
            'user_id' => auth()->id(),
            'role' => $role,
            'bidangId' => $bidangId,
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'kasBankIds' => $kasBankIds,
        ]);

        Log::info('DEBUG ARUS KAS COUNT CASHLEDGERS', [
            'count' => $cashLedgers->flatten()->count()
        ]);

        // Struktur per kategori PSAK 2
        $arus = [
            'operasional' => [],
            'investasi' => [],
            'pendanaan' => [],
        ];

        foreach ($cashLedgers as $transaksiId => $rows) {
            $transaksi = $rows->first()->transaksi;
            if (!$transaksi) {
                continue;
            }

            // Kas normalnya debit: delta = debit - kredit (Arus Kas Bersih)
            $totalKasDebit = (float) $rows->sum('debit');
            $totalKasKredit = (float) $rows->sum('credit');
            $deltaKas = $totalKasDebit - $totalKasKredit;

            if (abs($deltaKas) < 0.005) {
                continue; // tidak ada perubahan kas bersih
            }

            // Tentukan akun lawan (non-kas)
            $akunUtama = $transaksi->akunKeuangan;      // akun_keuangan_id
            $akunLawan = $transaksi->parentAkunKeuangan; // parent_akun_id

            $candidate = collect([$akunUtama, $akunLawan])
                ->filter(function ($a) use ($kasBankIds) {
                    return $a && !in_array($a->id, $kasBankIds, true);
                });

            // Transfer antar kas/bank → abaikan (mutasi internal)
            if ($candidate->count() !== 1) {
                continue;
            }

            /** @var \App\Models\AkunKeuangan $nonCash */
            $nonCash = $candidate->first();

            $kategori = $nonCash->cashflow_category ?? 'operasional';
            if (!isset($arus[$kategori])) {
                $kategori = 'operasional';
            }

            $key = $nonCash->id;

            if (!isset($arus[$kategori][$key])) {
                $arus[$kategori][$key] = [
                    'akun' => $nonCash,
                    'saldo' => 0.0,
                ];
            }

            // Akumulasi perubahan kas per akun lawan
            $arus[$kategori][$key]['saldo'] += $deltaKas;
        }

        // Total Bersih per kategori
        $totalOperasional = collect($arus['operasional'])->sum('saldo');
        $totalInvestasi = collect($arus['investasi'])->sum('saldo');
        $totalPendanaan = collect($arus['pendanaan'])->sum('saldo');

        $kenaikanPenurunanKas = $totalOperasional + $totalInvestasi + $totalPendanaan;

        // ================= START PERBAIKAN: Hitung Masuk & Keluar =================

        // Arus Kas Masuk adalah bagian positif dari total bersih.
        // Arus Kas Keluar adalah bagian positif dari total bersih yang dinegatifkan.

        $kasOperasionalMasuk = max(0, $totalOperasional);
        $kasOperasionalKeluar = max(0, -$totalOperasional);

        $kasInvestasiMasuk = max(0, $totalInvestasi);
        $kasInvestasiKeluar = max(0, -$totalInvestasi);

        $kasPendanaanMasuk = max(0, $totalPendanaan);
        $kasPendanaanKeluar = max(0, -$totalPendanaan);

        // Total Arus Kas Masuk dan Keluar Global
        $totalKasMasuk = $kasOperasionalMasuk + $kasInvestasiMasuk + $kasPendanaanMasuk;
        $totalKasKeluar = $kasOperasionalKeluar + $kasInvestasiKeluar + $kasPendanaanKeluar;

        // ================= END PERBAIKAN =================

        // ================= OPENING & ENDING CASH =================

        $kasBankAkun = AkunKeuangan::where('is_kas_bank', true)
            ->orderBy('kode_akun')
            ->get();

        $hitungSaldoKas = function (Carbon $tanggal) use ($kasBankAkun, $role, $bidangId) {
            return Ledger::select(
                'akun_keuangan_id',
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
                ->whereIn('akun_keuangan_id', $kasBankAkun->pluck('id'))
                ->whereHas('transaksi', function ($q) use ($tanggal, $role, $bidangId) {
                    $q->whereDate('tanggal_transaksi', '<=', $tanggal);
                    if ($role === 'Bidang' && $bidangId) {
                        $q->where('bidang_name', $bidangId);
                    }
                })
                ->groupBy('akun_keuangan_id')
                ->get()
                ->sum(function ($row) use ($kasBankAkun) {
                    $akun = $kasBankAkun->firstWhere('id', $row->akun_keuangan_id);
                    // Saldo normal debit: Debit - Kredit. Saldo normal kredit: Kredit - Debit
                    return $akun->saldo_normal === 'debit'
                        ? ($row->total_debit - $row->total_credit)
                        : ($row->total_credit - $row->total_debit);
                });
        };

        // Saldo awal = saldo s.d. H-1
        $openingCash = $hitungSaldoKas($startDate->copy()->subDay());
        // Saldo akhir = saldo s.d. endDate
        $endingCash = $hitungSaldoKas($endDate);

        $reconKenaikan = $endingCash - $openingCash;

        // Pastikan semua variabel yang dibutuhkan view dikembalikan
        return [
            'role' => $role,
            'bidangId' => $bidangId,
            'startDate' => $startDate,
            'endDate' => $endDate,

            'arus' => $arus,
            'totalOperasional' => $totalOperasional,
            'totalInvestasi' => $totalInvestasi,
            'totalPendanaan' => $totalPendanaan,
            'kenaikanPenurunanKas' => $kenaikanPenurunanKas,

            // Variabel Arus Kas Masuk/Keluar yang Baru Didefinisikan:
            'kasOperasionalMasuk' => $kasOperasionalMasuk,
            'kasOperasionalKeluar' => $kasOperasionalKeluar,
            'kasInvestasiMasuk' => $kasInvestasiMasuk,
            'kasInvestasiKeluar' => $kasInvestasiKeluar,
            'kasPendanaanMasuk' => $kasPendanaanMasuk,
            'kasPendanaanKeluar' => $kasPendanaanKeluar,
            'totalKasMasuk' => $totalKasMasuk,
            'totalKasKeluar' => $totalKasKeluar,

            'openingCash' => $openingCash,
            'endingCash' => $endingCash,
            'reconKenaikan' => $reconKenaikan,
        ];
    }

    // ====== WEB VIEW: Arus Kas ======
    public function arusKas(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildArusKasData($startDate, $endDate);

        return view('laporan.arus_kas', $data);
    }

    // ====== EXPORT: Arus Kas PDF ======

    public function exportArusKasPdf(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildArusKasData($startDate, $endDate);

        $pdf = Pdf::loadView('laporan.export.arus_kas_pdf', $data)
            ->setPaper('a4', 'portrait');

        $name = "Laporan_Arus_Kas_{$startDate->toDateString()}_{$endDate->toDateString()}.pdf";

        return $pdf->download($name);
    }

    // ====== EXPORT: Arus Kas Excel ======
    public function exportArusKasExcel(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildArusKasData($startDate, $endDate);

        $name = "Laporan_Arus_Kas_{$startDate->toDateString()}_{$endDate->toDateString()}.xlsx";

        return Excel::download(
            new LaporanExport('laporan.export.arus_kas_export_excel', $data),
            $name
        );
    }

    /*********************************************************
     *  ========== 2) POSISI KEUANGAN (NERACA PSAK 45) ==========
     *********************************************************/

    /**
     * Core builder Posisi Keuangan (dipakai web + export).
     */
    private function buildPosisiKeuanganData(Carbon $endDate): array
    {
        $map = $this->resolveKasBankForUser();
        $role = $map['role'];
        $bidangId = $map['bidangId'];
        $kasId = $map['kasId'];
        $bankId = $map['bankId'];

        // Ambil semua akun yang sudah punya kategori_psak
        $akunList = AkunKeuangan::whereNotNull('kategori_psak')
            ->orderBy('kode_akun')
            ->get();

        // Aggregate ledger s.d. endDate (TANPA filter bidang) → saldo global by akun
        $saldoPerAkun = Ledger::select(
            'akun_keuangan_id',
            DB::raw('SUM(debit)  as total_debit'),
            DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('transaksi', function ($q) use ($endDate, $role, $bidangId) {
                $q->whereDate('tanggal_transaksi', '<=', $endDate);

                // 🔐 FILTER BIDANG
                if ($role === 'Bidang' && $bidangId) {
                    $q->where('bidang_name', $bidangId);
                }
            })
            ->groupBy('akun_keuangan_id')
            ->get()
            ->keyBy('akun_keuangan_id');

        // Inisialisasi kelompok PSAK
        $kelompok = [
            'aset_lancar' => [],
            'aset_tidak_lancar' => [],
            'liabilitas_jangka_pendek' => [],
            'liabilitas_jangka_panjang' => [],
            'aset_neto_tidak_terikat' => [],
            'aset_neto_terikat_temporer' => [],
            'aset_neto_terikat_permanen' => [],
        ];

        foreach ($akunList as $akun) {
            $agg = $saldoPerAkun->get($akun->id);
            $saldo = $this->hitungSaldoAkun($akun, $agg);

            if (abs($saldo) < 0.005) {
                continue;
            }

            // 🔐 Bidang hanya boleh lihat kas/bank miliknya
            if ($role === 'Bidang' && $akun->is_kas_bank) {
                if (!in_array($akun->id, [$kasId, $bankId], true)) {
                    continue;
                }
            }

            $kategori = $akun->kategori_psak;

            if (!isset($kelompok[$kategori])) {
                continue;
            }

            $kelompok[$kategori][] = [
                'akun' => $akun,
                'saldo' => $saldo,
            ];
        }

        // Total per kelompok
        $total = [];
        foreach ($kelompok as $key => $rows) {
            $total[$key] = collect($rows)->sum('saldo');
        }

        $totalAset = ($total['aset_lancar'] ?? 0) + ($total['aset_tidak_lancar'] ?? 0);
        $totalLiab = ($total['liabilitas_jangka_pendek'] ?? 0) + ($total['liabilitas_jangka_panjang'] ?? 0);
        $totalAN = ($total['aset_neto_tidak_terikat'] ?? 0)
            + ($total['aset_neto_terikat_temporer'] ?? 0)
            + ($total['aset_neto_terikat_permanen'] ?? 0);

        $selisih = $totalAset - ($totalLiab + $totalAN);

        // Summary kas/bank semua bidang (hanya untuk Bendahara)
        $saldoKasTotal = 0;
        $saldoBankTotal = 0;

        if ($role === 'Bendahara') {
            // Kas = kas-bank dengan kode_akun diawali '101'
            $akunKasAll = $akunList->filter(function ($a) {
                return $a->is_kas_bank && str_starts_with((string) $a->kode_akun, '101');
            });

            foreach ($akunKasAll as $akunKas) {
                $aggKas = $saldoPerAkun->get($akunKas->id);
                $saldoKasTotal += $this->hitungSaldoAkun($akunKas, $aggKas);
            }

            // Bank = kas-bank dengan kode_akun diawali '102'
            $akunBankAll = $akunList->filter(function ($a) {
                return $a->is_kas_bank && str_starts_with((string) $a->kode_akun, '102');
            });

            foreach ($akunBankAll as $akunBank) {
                $aggBank = $saldoPerAkun->get($akunBank->id);
                $saldoBankTotal += $this->hitungSaldoAkun($akunBank, $aggBank);
            }
        }

        $totalKeuanganSemuaBidang = $saldoKasTotal + $saldoBankTotal;

        return [
            'role' => $role,
            'bidangId' => $bidangId,

            'endDate' => $endDate,

            'kelompok' => $kelompok,
            'total' => $total,
            'totalAset' => $totalAset,
            'totalLiabilitas' => $totalLiab,
            'totalAsetNeto' => $totalAN,
            'selisih' => $selisih,

            'saldoKasTotal' => $saldoKasTotal,
            'saldoBankTotal' => $saldoBankTotal,
            'totalKeuanganSemuaBidang' => $totalKeuanganSemuaBidang,
        ];
    }

    // ====== WEB VIEW: Posisi Keuangan ======
    public function posisiKeuangan(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildPosisiKeuanganData($endDate);

        return view('laporan.posisi_keuangan', $data);
    }

    // ====== EXPORT: Posisi Keuangan PDF ======
    public function exportPosisiKeuanganPdf(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildPosisiKeuanganData($endDate);

        $pdf = Pdf::loadView('laporan.export.posisi_keuangan_pdf', $data)
            ->setPaper('a4', 'portrait');

        $suffix = $endDate->toDateString();
        $name = 'Laporan_Posisi_Keuangan_' . $suffix . '.pdf';

        return $pdf->download($name);
    }

    // ====== EXPORT: Posisi Keuangan Excel ======
    public function exportPosisiKeuanganExcel(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildPosisiKeuanganData($endDate);

        $suffix = $endDate->toDateString();
        $name = 'Laporan_Posisi_Keuangan_' . $suffix . '.xlsx';

        return Excel::download(
            new LaporanExport('laporan.export.posisi_keuangan_export_excel', $data),
            $name
        );
    }

    /*********************************************************
     *  ========== 3) LAPORAN AKTIVITAS (PSAK 45) ==========
     *********************************************************/

    /**
     * Core builder Aktivitas (dipakai web + export).
     */
    private function buildAktivitasData(Carbon $startDate, Carbon $endDate): array
    {
        $map = $this->resolveKasBankForUser();
        $role = $map['role'];
        $bidangId = $map['bidangId'];

        // Akun pendapatan & beban yang sudah dimapping PSAK
        $akunPendapatan = AkunKeuangan::where('kategori_psak', 'pendapatan')
            ->orderBy('kode_akun')
            ->get();

        $akunBeban = AkunKeuangan::where('kategori_psak', 'beban')
            ->orderBy('kode_akun')
            ->get();

        // Aggregate ledger per akun dalam periode
        $saldoPerAkun = Ledger::select(
            'akun_keuangan_id',
            DB::raw('SUM(debit)  as total_debit'),
            DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate, $role, $bidangId) {
                $q->whereDate('tanggal_transaksi', '>=', $startDate)
                    ->whereDate('tanggal_transaksi', '<=', $endDate);

                if ($role === 'Bidang' && $bidangId) {
                    $q->where('bidang_name', $bidangId);
                }
            })
            ->groupBy('akun_keuangan_id')
            ->get()
            ->keyBy('akun_keuangan_id');

        // Debugging Info
        // dd([
        //     'role' => $role,
        //     'bidangId' => $bidangId,
        //     'akunPendapatan_ids' => $akunPendapatan->pluck('id', 'kode_akun'),
        //     'akunBeban_ids' => $akunBeban->pluck('id', 'kode_akun'),
        //     'saldoPerAkun_keys' => $saldoPerAkun->keys(), // daftar akun yang punya saldo
        //     'saldoPerAkun_sample' => $saldoPerAkun->take(10)->map(function ($r) {
        //         return [
        //             'akun_id' => $r->akun_keuangan_id,
        //             'total_debit' => $r->total_debit,
        //             'total_credit' => $r->total_credit,
        //         ];
        //     }),
        // ]);

        // ===== Pendapatan (kelompok berdasarkan pembatasan) =====
        $pendapatan = [
            'tidak_terikat' => [],
            'terikat_temporer' => [],
            'terikat_permanen' => [],
        ];

        $totalPendapatan = [
            'tidak_terikat' => 0,
            'terikat_temporer' => 0,
            'terikat_permanen' => 0,
        ];

        foreach ($akunPendapatan as $akun) {
            $agg = $saldoPerAkun->get($akun->id);
            $saldo = $this->hitungSaldoAkun($akun, $agg);
            if (abs($saldo) < 0.005) {
                continue;
            }

            $nilaiLaporan = abs($saldo);

            $pembatasan = $akun->pembatasan ?? 'tidak_terikat';
            if (!isset($pendapatan[$pembatasan])) {
                $pembatasan = 'tidak_terikat';
            }

            $pendapatan[$pembatasan][] = [
                'akun' => $akun,
                'saldo' => $nilaiLaporan,
            ];

            $totalPendapatan[$pembatasan] += $nilaiLaporan;
        }

        // ===== Pisahkan Beban Sesuai Pembatasan =====
        $bebanTidakTerikat = [];
        $bebanTerikatTemporer = [];
        $bebanTerikatPermanen = [];

        $totalBebanTidakTerikat = 0;
        $totalBebanTemporer = 0;
        $totalBebanPermanen = 0;

        foreach ($akunBeban as $akun) {
            $agg = $saldoPerAkun->get($akun->id);
            $saldo = $this->hitungSaldoAkun($akun, $agg);

            if (abs($saldo) < 0.005) {
                continue;
            }

            $nilai = abs($saldo);

            // Mapping pembatasan dari kolom atau dari kode akun
            $pembatasan = $akun->pembatasan
                ?? ($akun->kode_akun < 310 ? 'tidak_terikat'
                    : ($akun->kode_akun < 330 ? 'terikat_temporer'
                        : 'terikat_permanen'));

            if ($pembatasan === 'tidak_terikat') {
                $bebanTidakTerikat[] = ['akun' => $akun, 'saldo' => $nilai];
                $totalBebanTidakTerikat += $nilai;

            } elseif ($pembatasan === 'terikat_temporer') {
                $bebanTerikatTemporer[] = ['akun' => $akun, 'saldo' => $nilai];
                $totalBebanTemporer += $nilai;

            } elseif ($pembatasan === 'terikat_permanen') {
                $bebanTerikatPermanen[] = ['akun' => $akun, 'saldo' => $nilai];
                $totalBebanPermanen += $nilai;
            }
        }

        // ===== Perubahan Aset Neto =====
        $perubahanTidakTerikat =
            ($totalPendapatan['tidak_terikat'] ?? 0)
            - $totalBebanTidakTerikat;

        $perubahanTemporer =
            ($totalPendapatan['terikat_temporer'] ?? 0)
            - $totalBebanTemporer;

        $perubahanPermanen =
            ($totalPendapatan['terikat_permanen'] ?? 0)
            - $totalBebanPermanen;

        $totalPerubahanAsetNeto =
            $perubahanTidakTerikat
            + $perubahanTemporer
            + $perubahanPermanen;


        return [
            'role' => $role,
            'bidangId' => $bidangId,

            'startDate' => $startDate,
            'endDate' => $endDate,

            // Pendapatan
            'pendapatan' => $pendapatan,
            'totalPendapatan' => $totalPendapatan,

            // Beban per pembatasan
            'bebanTidakTerikat' => $bebanTidakTerikat,
            'bebanTerikatTemporer' => $bebanTerikatTemporer,
            'bebanTerikatPermanen' => $bebanTerikatPermanen,

            // Total per pembatasan
            'totalBebanTidakTerikat' => $totalBebanTidakTerikat,
            'totalBebanTemporer' => $totalBebanTemporer,
            'totalBebanPermanen' => $totalBebanPermanen,

            // Perubahan aset neto per pembatasan
            'perubahanTidakTerikat' => $perubahanTidakTerikat,
            'perubahanTemporer' => $perubahanTemporer,
            'perubahanPermanen' => $perubahanPermanen,

            // Total perubahan aset neto
            'totalPerubahanAsetNeto' => $totalPerubahanAsetNeto,
        ];
    }

    // ====== WEB VIEW: Aktivitas ======
    public function aktivitas(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildAktivitasData($startDate, $endDate);

        return view('laporan.aktivitas', $data);
    }

    // ====== EXPORT: Aktivitas PDF ======
    public function exportAktivitasPdf(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildAktivitasData($startDate, $endDate);

        $pdf = Pdf::loadView('laporan.export.aktivitas_pdf', $data)
            ->setPaper('a4', 'portrait');

        $name = "Laporan_Aktivitas_{$startDate->toDateString()}_{$endDate->toDateString()}.pdf";

        return $pdf->download($name);
    }

    // ====== EXPORT: Aktivitas Excel ======
    public function exportAktivitasExcel(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $data = $this->buildAktivitasData($startDate, $endDate);

        $name = "Laporan_Aktivitas_{$startDate->toDateString()}_{$endDate->toDateString()}.xlsx";

        return Excel::download(
            new LaporanExport('laporan.export.aktivitas_export_excel', $data),
            $name
        );
    }
}
