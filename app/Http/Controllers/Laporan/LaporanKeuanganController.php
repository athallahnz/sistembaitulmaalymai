<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaksi;
use App\Models\AkunKeuangan;
use App\Models\Ledger;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\LaporanService;



class LaporanKeuanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function arusKas(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $user = auth()->user();

        // Helper untuk filter transaksi non-internal antar akun kas
        $filterTransaksiKasEksternal = function ($transaksi) {
            return !(
                isKasAkun($transaksi->akunAsal) &&
                isKasAkun($transaksi->akunTujuan)
            );
        };

        // Ambil data transaksi sesuai kategori cashflow, lalu filter agar bukan antar akun kas
        $transaksiOperasional = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'operasional'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiInvestasi = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'investasi'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiPendanaan = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'pendanaan'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        // Hitung total kas masuk dan keluar
        $kasOperasionalMasuk = $transaksiOperasional->where('type', 'penerimaan')->sum('amount');
        $kasOperasionalKeluar = $transaksiOperasional->where('type', 'pengeluaran')->sum('amount');

        $kasInvestasiMasuk = $transaksiInvestasi->where('type', 'penerimaan')->sum('amount');
        $kasInvestasiKeluar = $transaksiInvestasi->where('type', 'pengeluaran')->sum('amount');

        $kasPendanaanMasuk = $transaksiPendanaan->where('type', 'penerimaan')->sum('amount');
        $kasPendanaanKeluar = $transaksiPendanaan->where('type', 'pengeluaran')->sum('amount');

        // Total arus kas
        $totalKasMasuk = $kasOperasionalMasuk + $kasInvestasiMasuk + $kasPendanaanMasuk;
        $totalKasKeluar = $kasOperasionalKeluar + $kasInvestasiKeluar + $kasPendanaanKeluar;

        return view('laporan.arus_kas', compact(
            'startDate',
            'endDate',
            'kasOperasionalMasuk',
            'kasOperasionalKeluar',
            'kasInvestasiMasuk',
            'kasInvestasiKeluar',
            'kasPendanaanMasuk',
            'kasPendanaanKeluar',
            'totalKasMasuk',
            'totalKasKeluar'
        ));
    }

    // 📥 Export Arus Kas ke PDF
    public function exportArusKasPDF(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $user = auth()->user();

        // Helper untuk filter transaksi non-internal antar akun kas
        $filterTransaksiKasEksternal = function ($transaksi) {
            return !(
                isKasAkun($transaksi->akunAsal) &&
                isKasAkun($transaksi->akunTujuan)
            );
        };

        // Ambil data transaksi sesuai kategori cashflow, lalu filter agar bukan antar akun kas
        $transaksiOperasional = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'operasional'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiInvestasi = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'investasi'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiPendanaan = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'pendanaan'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        // Hitung total kas masuk dan keluar
        $kasOperasionalMasuk = $transaksiOperasional->where('type', 'penerimaan')->sum('amount');
        $kasOperasionalKeluar = $transaksiOperasional->where('type', 'pengeluaran')->sum('amount');

        $kasInvestasiMasuk = $transaksiInvestasi->where('type', 'penerimaan')->sum('amount');
        $kasInvestasiKeluar = $transaksiInvestasi->where('type', 'pengeluaran')->sum('amount');

        $kasPendanaanMasuk = $transaksiPendanaan->where('type', 'penerimaan')->sum('amount');
        $kasPendanaanKeluar = $transaksiPendanaan->where('type', 'pengeluaran')->sum('amount');

        // Total arus kas
        $totalKasMasuk = $kasOperasionalMasuk + $kasInvestasiMasuk + $kasPendanaanMasuk;
        $totalKasKeluar = $kasOperasionalKeluar + $kasInvestasiKeluar + $kasPendanaanKeluar;

        $pdf = Pdf::loadView('laporan.export.arus_kas_pdf', compact(
            'startDate',
            'endDate',
            'kasOperasionalMasuk',
            'kasOperasionalKeluar',
            'kasInvestasiMasuk',
            'kasInvestasiKeluar',
            'kasPendanaanMasuk',
            'kasPendanaanKeluar',
            'totalKasMasuk',
            'totalKasKeluar'
        ));
        return $pdf->download('laporan_arus_kas.pdf');
    }

    // 📊 Laporan Posisi Keuangan
    public function posisiKeuangan(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()->endOfMonth()));


        $kategoriAkun = ['asset', 'liability', 'equity'];
        $data = [];

        foreach ($kategoriAkun as $kategori) {
            $akuns = AkunKeuangan::with([
                'transaksis' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
                }
            ])->where('tipe_akun', $kategori)->get();

            $total = $akuns->sum('saldo'); // saldo tetap dari accessor

            $data[$kategori] = [
                'akuns' => $akuns,
                'total' => $total,
            ];
        }

        return view('laporan.posisi_keuangan', compact('data', 'startDate', 'endDate'));
    }

    // 📈 Laporan Aktivitas / Laba Rugi
    public function labaRugi()
    {
        $pendapatan = AkunKeuangan::where('tipe_akun', 'revenue')->with('transaksis')->get();
        $beban = AkunKeuangan::where('tipe_akun', 'expense')->with('transaksis')->get();

        return view('laporan.laba_rugi', compact('pendapatan', 'beban'));
    }

    // 📋 Neraca Saldo
    public function neracaSaldo(Request $request)
    {
        $user = auth()->user();
        $bidangName = $user->bidang_name;

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        // Ambil akun keuangan utama (tanpa parent_id)
        $akunKeuangan = AkunKeuangan::whereNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'liability', 'expense'])
            ->get();

        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id

        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011;
        } else {
            $akunKas = [
                1 => 1012,
                2 => 1013,
                3 => 1014,
                4 => 1015,
            ];
            $akun_keuangan_id = $akunKas[$bidang_id] ?? null;
        }

        \Log::info("Akun Keuangan ID: " . ($akun_keuangan_id ?? 'NULL'));

        if (!$bidang_id || !$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan bidang_name yang diberikan ada dalam daftar
        if (isset($akunKas[$bidang_id])) {
            $akun_keuangan_id = $akunKas[$bidang_id];

            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang_id)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil saldo terakhir (data terbaru)
        } else {
            $lastSaldo = null; // Jika bidang_name tidak ditemukan, return null
        }

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Kas dianggap 0

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

        \Log::info("Akun Keuangan ID: " . ($akun_keuangan_id ?? 'NULL'));

        if (!$bidang_id || !$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan bidang_name yang diberikan ada dalam daftar
        if (isset($akunBank[$bidang_id])) {
            $akun_keuangan_id = $akunBank[$bidang_id];

            $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang_id)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();
        } else {
            $lastTransaksi = null; // Jika bidang_name tidak ditemukan, return null
        }

        $saldoBank = $lastTransaksi ? (float) $lastTransaksi->saldo : 0;

        $jumlahPiutang = Piutang::where('bidang_name', $bidangName)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');

        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024, 3025, 3026, 3027])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidangName)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahDonasi = Transaksi::whereIn('parent_akun_id', [2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaOperasional = Transaksi::whereIn('parent_akun_id', [
            3031,
            3032,
            3033,
            3034,
            3035,
            3036,
            3037,
            3038,
            3039,
            30310,
            30311,
            30312
        ])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahBiayaKegiatanSiswa = Transaksi::whereIn('parent_akun_id', [3041, 3042, 3043])
            ->where('bidang_name', $bidangName)
            ->sum('amount');
        $jumlahBiayaPemeliharaan = Transaksi::whereIn('parent_akun_id', [3051, 3052])
            ->where('bidang_name', $bidangName)
            ->sum('amount');
        $jumlahBiayaSosial = Transaksi::whereIn('parent_akun_id', [3061, 3062])
            ->where('bidang_name', $bidangName)
            ->sum('amount');
        $jumlahBiayaPerlengkapanExtra = Transaksi::whereIn('parent_akun_id', [3071, 3072])
            ->where('bidang_name', $bidangName)
            ->sum('amount');
        $jumlahBiayaSeragam = Transaksi::whereIn('parent_akun_id', [3081, 3082])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        return view('laporan.neraca_saldo', compact(
            'akunKeuangan',
            'startDate',
            'endDate',
            'saldoKas',
            'saldoBank',
            'jumlahHutang',
            'jumlahDonasi',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatanSiswa',
            'jumlahBiayaPemeliharaan',
            'jumlahBiayaSosial',
            'jumlahBiayaPerlengkapanExtra',
            'jumlahBiayaSeragam'
        ));
    }

    public function neracaSaldoBendahara(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        // Ambil akun keuangan utama (tanpa parent_id)
        $akunKeuangan = AkunKeuangan::whereNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'liability', 'expense'])
            ->get();

        $bidangName = auth()->user()->bidang_name; // Bidang name dari user saat ini

        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id

        $akunKas = [
            'Bendahara' => 1011,
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ];

        $akunBank = [
            'Bendahara' => 1021,
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        $saldoKasTotal = 0;
        $saldoBankTotal = 0;

        foreach ($akunKas as $bidang => $akun_keuangan_id) {
            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoKasTotal += $lastSaldo ? (float) $lastSaldo->saldo : 0;
        }

        foreach ($akunBank as $bidang => $akun_keuangan_id) {
            $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoBankTotal += $lastTransaksi ? (float) $lastTransaksi->saldo : 0;
        }

        // **Total Keuangan Semua Bidang (Kas + Bank)**
        $totalKeuanganSemuaBidang = $saldoKasTotal + $saldoBankTotal;

        $jumlahPiutang = Piutang::where('status', 'belum_lunas')->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');

        // Ambil saldo untuk Beban Gaji
        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])
            ->sum('amount');

        $jumlahHutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');

        $jumlahDonasi = Transaksi::whereIn('parent_akun_id', [2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028])
            ->sum('amount');

        // Ambil saldo untuk Biaya Operasional
        $jumlahBiayaOperasional = Transaksi::whereIn('parent_akun_id', [
            3031,
            3032,
            3033,
            3034,
            3035,
            3036,
            3037,
            3038,
            3039,
            30310,
            30311,
            30312
        ])->sum('amount');

        // Ambil saldo untuk Biaya Kegiatan
        $jumlahBiayaKegiatanSiswa = Transaksi::whereIn('parent_akun_id', [3041, 3042])
            ->sum('amount');

        $jumlahBiayaPemeliharaan = Transaksi::whereIn('parent_akun_id', [3051, 3052])
            ->sum('amount');
        $jumlahBiayaSosial = Transaksi::whereIn('parent_akun_id', [3061, 3062])
            ->sum('amount');
        $jumlahBiayaPerlengkapanExtra = Transaksi::whereIn('parent_akun_id', [3071, 3072])
            ->sum('amount');
        $jumlahBiayaSeragam = Transaksi::whereIn('parent_akun_id', [3081, 3082])
            ->sum('amount');

        return view('laporan.neraca_saldo', compact(
            'akunKeuangan',
            'startDate',
            'endDate',
            'saldoKasTotal',
            'saldoBankTotal',
            'jumlahHutang',
            'jumlahDonasi',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatanSiswa',
            'jumlahBiayaPemeliharaan',
            'jumlahBiayaSosial',
            'jumlahBiayaPerlengkapanExtra',
            'jumlahBiayaSeragam'
        ));
    }

    private function calculateTotalKas()
    {
        $bidangNames = User::whereNotNull('bidang_name')->pluck('bidang_name');

        $totalKas = $bidangNames->sum(function ($bidangName) {
            return Transaksi::where('akun_keuangan_id', 101)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last()?->saldo ?? 0; // Ambil nilai saldo terakhir atau 0 jika tidak ada data
        });

        return $totalKas;
    }

    private function calculateTotalKeuanganBidang()
    {
        $bidangNames = User::whereNotNull('bidang_name')->pluck('bidang_name');
        $totalKeuangan = 0;

        foreach ($bidangNames as $bidangName) {
            // Ambil saldo terakhir untuk akun 101
            $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last() // Ambil baris terakhir (data terbaru)
                    ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data

            // Ambil saldo terakhir untuk akun 102
            $lastSaldo102 = Transaksi::where('akun_keuangan_id', 102)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last() // Ambil baris terakhir (data terbaru)
                    ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data

            $totalKeuangan += $lastSaldo101 + $lastSaldo102;
        }

        return $totalKeuangan;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        //
    }
}
