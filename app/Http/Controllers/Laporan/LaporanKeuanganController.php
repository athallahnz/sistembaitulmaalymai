<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\AkunKeuangan;
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

        // Konversi input ke Carbon
        $startDate = $request->has('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        // Ambil akun keuangan yang parent_id-nya NULL
        $akunKeuangan = AkunKeuangan::whereIn('tipe_akun', ['asset', 'liability', 'expense'])
            ->whereNull('parent_id')
            ->with([
                'transaksis' => function ($query) use ($user, $startDate, $endDate) {
                    // Filter transaksi berdasarkan bidang pengguna & tanggal
                    $query->whereBetween('tanggal_transaksi', [$startDate->toDateString(), $endDate->toDateString()]);

                    if ($user->role === 'Bidang') {
                        $query->where('bidang_name', $user->bidang_name);
                    }
                }
            ])
            ->get();

        return view('laporan.neraca_saldo', compact('akunKeuangan', 'startDate', 'endDate'));
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
