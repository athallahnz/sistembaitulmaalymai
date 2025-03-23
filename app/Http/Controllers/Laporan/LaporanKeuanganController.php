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

    // 🏦 Laporan Arus Kas
    public function arusKas(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $user = auth()->user();

        // Ambil transaksi berdasarkan bidang pengguna (tanpa join ke users)
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan')
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);

        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        $penerimaan = $transaksi->where('type', 'penerimaan')->sum('amount');
        $pengeluaran = $transaksi->where('type', 'pengeluaran')->sum('amount');

        return view('laporan.arus_kas', compact('penerimaan', 'pengeluaran', 'startDate', 'endDate'));
    }

    // 📥 Export Arus Kas ke PDF
    public function exportArusKasPDF(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $user = auth()->user();

        // Ambil transaksi berdasarkan bidang pengguna (tanpa join ke users)
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan')
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);

        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        $penerimaan = $transaksi->where('type', 'penerimaan')->sum('amount');
        $pengeluaran = $transaksi->where('type', 'pengeluaran')->sum('amount');

        $pdf = Pdf::loadView('laporan.export.arus_kas_pdf', compact('penerimaan', 'pengeluaran', 'startDate', 'endDate'));
        return $pdf->download('laporan_arus_kas.pdf');
    }

    // 📊 Laporan Posisi Keuangan
    public function posisiKeuangan()
    {
        $assets = AkunKeuangan::where('tipe_akun', 'asset')->with('transaksis')->get();
        $liabilities = AkunKeuangan::where('tipe_akun', 'liability')->with('transaksis')->get();
        $equity = AkunKeuangan::where('tipe_akun', 'equity')->with('transaksis')->get();

        return view('laporan.posisi_keuangan', compact('assets', 'liabilities', 'equity'));
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

        // Ambil saldo terakhir untuk akun Kas (101) & Bank (102)
        $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
            ->where('bidang_name', $bidangName)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last()?->saldo ?? 0;

        $lastSaldo102 = Transaksi::where('akun_keuangan_id', 102)
            ->where('bidang_name', $bidangName)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last()?->saldo ?? 0;

        $jumlahPiutang = Piutang::where('bidang_name', $bidangName)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');
        // Ambil saldo untuk Beban Gaji
        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidangName)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahDonasi = Ledger::where('akun_keuangan_id', 202)
            ->whereHas('transaksi', function ($query) {
                $query->where('bidang_name', auth()->user()->bidang_name);
            })
            ->sum('credit');


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
        ])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        // Ambil saldo untuk Biaya Kegiatan
        $jumlahBiayaKegiatan = Transaksi::whereIn('parent_akun_id', [3041, 3042])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        return view('laporan.neraca_saldo', compact(
            'akunKeuangan',
            'startDate',
            'endDate',
            'lastSaldo101',
            'lastSaldo102',
            'jumlahHutang',
            'jumlahDonasi',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatan'
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

        // Konsolidasi bank untuk bidang saat ini
        $bankId = 102; // ID default akun bank
        $dataKonsolidasi = LaporanService::index($bankId, $bidangName);
        $totalSaldoBank = $dataKonsolidasi['saldo'];
        $transaksiBank = $dataKonsolidasi['transaksi'];

        // **Akumulasi total kas seluruh bidang**
        $totalseluruhKas = $this->calculateTotalKas();

        // **Akumulasi total seluruh bank dari semua bidang**
        $allFields = Transaksi::distinct()->pluck('bidang_name');
        $totalSeluruhBank = $allFields->reduce(function ($carry, $field) use ($bankId) {
            $dataKonsolidasiField = LaporanService::index($bankId, $field);
            return $carry + $dataKonsolidasiField['saldo'];
        }, 0);

        // Ambil saldo terakhir untuk akun Kas (101) & Bank (102)
        $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last()?->saldo ?? 0;

        $lastSaldo102 = Transaksi::where('akun_keuangan_id', 102)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last()?->saldo ?? 0;

        $jumlahPiutang = Piutang::where('status', 'belum_lunas')->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');

        // Ambil saldo untuk Beban Gaji
        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])
            ->sum('amount');

        $jumlahHutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');

        $jumlahDonasi = Ledger::where('akun_keuangan_id', 202)
            ->sum('credit');

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
        $jumlahBiayaKegiatan = Transaksi::whereIn('parent_akun_id', [3041, 3042])
            ->sum('amount');

        return view('laporan.neraca_saldo', compact(
            'akunKeuangan',
            'startDate',
            'endDate',
            'lastSaldo101',
            'lastSaldo102',
            'totalseluruhKas',
            'totalSeluruhBank',
            'jumlahHutang',
            'jumlahDonasi',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatan'
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
