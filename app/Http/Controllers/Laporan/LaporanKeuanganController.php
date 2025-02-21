<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\AkunKeuangan;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


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

        // Konversi input ke Carbon
        $startDate = $request->has('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

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

        // Ambil saldo untuk Piutang
        $jumlahPiutang = Transaksi::whereIn('parent_akun_id', [1031, 1032, 1033, 1034, 1035, 1036])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        // Ambil saldo untuk Beban Gaji
        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahHutang = Transaksi::whereIn('parent_akun_id', [2011, 2012, 2013, 2014])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

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
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatan'
        ));
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
