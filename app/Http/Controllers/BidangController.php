<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\LaporanService;
use Yajra\DataTables\Facades\DataTables;

class BidangController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bidang');  // Menggunakan middleware untuk role ketua
    }

    protected function getSaldoTerakhir(int $akunKeuanganId, $bidangName = null): float
    {
        $query = Transaksi::where('akun_keuangan_id', $akunKeuanganId)
            ->when(is_null($bidangName), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangName), fn($q) => $q->where('bidang_name', $bidangName));

        $row = $query->selectRaw("
                COALESCE(SUM(CASE
                    WHEN type = 'penerimaan' THEN amount
                    WHEN type = 'pengeluaran' THEN -amount
                    ELSE 0 END), 0
                ) AS saldo_akhir
            ")
            ->first();

        return (float) ($row->saldo_akhir ?? 0.0);
    }

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;

        $akunKas = $user->role === 'Bendahara' ? 1011 : [
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ][$bidangId] ?? null;

        $akunBank = $user->role === 'Bendahara' ? 1021 : [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ][$bidangId] ?? null;

        if (!$akunKas || !$akunBank) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        $saldoKas = $this->getSaldoTerakhir($akunKas, $user->role === 'Bendahara' ? null : $bidangId);
        $saldoBank = $this->getSaldoTerakhir($akunBank, $user->role === 'Bendahara' ? null : $bidangId);

        $totalKeuanganBidang = $saldoKas + $saldoBank;

        $jumlahTransaksi = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        $jumlahPiutang = Piutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::where('bidang_name', $bidangId)
            ->sum('jumlah');

        // Tetap gunakan akun langsung untuk asset jika itu adalah akun tunggal
        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        // Gunakan lookup dinamis berdasarkan parent_id (contoh parent ids: 202, 302, 303, dst.)
        // Jika struktur parent berbeda, ganti nilai parent id sesuai struktur akun Anda.
        $bidangName = auth()->user()->bidang_name;

        $jumlahDonasi = $this->sumTransaksiByParent(202, $bidangName);
        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahBebanGaji = $this->sumTransaksiByParent(302, $bidangName);
        $jumlahBiayaOperasional = $this->sumTransaksiByParent(303, $bidangName);
        $jumlahBiayaKegiatanSiswa = $this->sumTransaksiByParent(304, $bidangName);
        $jumlahBiayaPemeliharaan = $this->sumTransaksiByParent(305, $bidangName);
        $jumlahBiayaSosial = $this->sumTransaksiByParent(306, $bidangName);
        $jumlahBiayaPerlengkapanExtra = $this->sumTransaksiByParent(307, $bidangName);
        $jumlahBiayaSeragam = $this->sumTransaksiByParent(308, $bidangName);
        $jumlahBiayaPeningkatanSDM = $this->sumTransaksiByParent(309, $bidangName);
        $jumlahBiayadibayardimuka = $this->sumTransaksiByParent(310, $bidangName);

        return view('bidang.index', compact(
            'totalKeuanganBidang',
            'jumlahTransaksi',
            'saldoKas',
            'saldoBank',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'jumlahHutang',
            'hutangJatuhTempo',
            'jumlahDonasi',
            'jumlahPenyusutanAsset',
            'jumlahBebanGaji',
            'jumlahBiayaKegiatanSiswa',
            'jumlahBiayaOperasional',
            'jumlahBiayaPemeliharaan',
            'jumlahBiayaSosial',
            'jumlahBiayaPerlengkapanExtra',
            'jumlahBiayaSeragam',
            'jumlahBiayaPeningkatanSDM',
            'jumlahBiayadibayardimuka'
        ));
    }
    private function sumTransaksiByParent(int $parentId, $bidangName = null): float
    {
        $subAkunIds = AkunKeuangan::where('parent_id', $parentId)->pluck('id')->toArray();

        if (empty($subAkunIds)) {
            return 0.0;
        }

        $query = Transaksi::whereIn('parent_akun_id', $subAkunIds);

        if (!is_null($bidangName)) {
            $query->where('bidang_name', $bidangName);
        }

        return (float) $query->sum('amount');
    }

    public function showDetail(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL
        $type = $request->input('type'); // Ambil parent_akun_id dari URL
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (parent_akun_id = sub-akun)
        $transaksiData = Transaksi::when(!empty($subAkunIds), fn($q) => $q->whereIn('parent_akun_id', $subAkunIds))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($bidangId, fn($q) => $q->where('bidang_name', $bidangId))
            ->get();

        // Ambil nama_akun dari parent_akun_id
        $parentAkun = AkunKeuangan::find($parentAkunId);

        return view('bidang.detail', compact('transaksiData', 'parentAkunId', 'type', 'parentAkun', 'bidangId'));
    }

    // Menambahkan method untuk mengambil data transaksi berdasarkan parent_akun_id
    public function getDetailData(Request $request)
    {
        $bidangName = auth()->user()->bidang_name;

        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
            ->where('bidang_name', $bidangName);

        // Jika ada 'type', filter berdasarkan type
        if ($type) {
            $query->where('type', $type);
        }

        // Jika ada 'parent_akun_id', filter berdasarkan sub-akun dari parent
        if ($parentAkunId) {
            $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();
            $query->whereIn('parent_akun_id', $subAkunIds);
        }

        $transaksiData = $query->get();

        return DataTables::of($transaksiData)
            ->addColumn('akun_keuangan', function ($row) {
                return $row->akunKeuangan ? $row->akunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('parent_akun_keuangan', function ($row) {
                return $row->parentAkunKeuangan ? $row->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->make(true);
    }

}

