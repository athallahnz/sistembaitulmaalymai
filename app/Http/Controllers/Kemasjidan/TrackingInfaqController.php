<?php

namespace App\Http\Controllers\Kemasjidan;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Models\InfaqKemasjidan;
use App\Models\IuranBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrackingInfaqController extends Controller
{
    public function showLogin()
    {
        return view('bidang.kemasjidan.infaq.tracking-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'hp' => ['required', 'string'],
            'pin' => ['required', 'string', 'min:4', 'max:16'],
        ]);

        $warga = Warga::where('hp', $request->hp)->first();

        $pinOk = false;
        if ($warga && $warga->pin) {
            $pinOk = Hash::check($request->pin, (string) $warga->pin)
                || hash_equals((string) $warga->pin, (string) $request->pin);
        }

        if (!$warga || !$warga->pin || !$pinOk) {
            return back()->withInput()->with('error', 'Nomor HP atau PIN tidak valid.');
        }

        session(['warga_id' => $warga->id]);
        $request->session()->regenerate();

        return redirect()->route('warga.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('warga_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('warga.login.form')->with('success', 'Anda telah keluar.');
    }

    private function bulanMap(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    private function requireWargaSession()
    {
        $wargaId = (int) session('warga_id');
        if (!$wargaId) {
            return null;
        }
        return $wargaId;
    }

    /** Dashboard (bisa kamu pakai sebagai landing page warga) */
    public function dashboard(Request $request)
    {
        $wargaId = (int) session('warga_id');
        if (!$wargaId) {
            return redirect()->route('warga.login.form');
        }

        $warga = Warga::findOrFail($wargaId);
        $tahun = (int) ($request->get('tahun', now()->year));

        // kepala keluarga id (agar iuran konsisten)
        $kepalaId = $warga->warga_id ? (int) $warga->warga_id : (int) $warga->id;

        // ===== RINGKAS INFAQ KEMASJIDAN =====
        $infaqTotal = (float) InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->sum('nominal');

        $infaqLunasCount = (int) InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->where('nominal', '>', 0)
            ->count();

        // ===== RINGKAS IURAN SOSIAL =====
        $iuranTotalBayar = (int) IuranBulanan::query()
            ->where('warga_kepala_id', $kepalaId)
            ->where('tahun', $tahun)
            ->sum('nominal_bayar');

        $iuranTotalTagihan = (int) IuranBulanan::query()
            ->where('warga_kepala_id', $kepalaId)
            ->where('tahun', $tahun)
            ->sum('nominal_tagihan');

        $iuranLunasCount = (int) IuranBulanan::query()
            ->where('warga_kepala_id', $kepalaId)
            ->where('tahun', $tahun)
            ->where('status', 'lunas')
            ->count();

        return view('bidang.kemasjidan.infaq.tracking-dashboard', [
            'warga' => $warga,
            'tahun' => $tahun,

            'infaqTotal' => $infaqTotal,
            'infaqLunasCount' => $infaqLunasCount,

            'iuranTotalBayar' => $iuranTotalBayar,
            'iuranTotalTagihan' => $iuranTotalTagihan,
            'iuranLunasCount' => $iuranLunasCount,
        ]);
    }

    /** Tracking khusus Infaq */
    public function trackingInfaq(Request $request)
    {
        $wargaId = $this->requireWargaSession();
        if (!$wargaId)
            return redirect()->route('warga.login.form');

        $warga = Warga::findOrFail($wargaId);
        $tahun = (int) ($request->get('tahun', now()->year));
        $bulanMap = $this->bulanMap();

        $rows = InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('bulan');

        $status = [];
        $total = 0.0;

        foreach (range(1, 12) as $b) {
            $trx = $rows->get($b);
            $nom = (float) ($trx->nominal ?? 0);
            $status[$b] = [
                'bulan_angka' => $b,
                'bulan_nama' => $bulanMap[$b],
                'nominal' => $nom,
                'lunas' => $nom > 0,
                'tanggal' => $trx?->tanggal,
                'metode_bayar' => $trx?->metode_bayar,
                'trx_id' => $trx?->id,
            ];
            $total += $nom;
        }

        return view('bidang.kemasjidan.infaq.tracking-infaq', [
            'warga' => $warga,
            'tahun' => $tahun,
            'status' => $status,
            'total' => $total,
            'bulanMap' => $bulanMap,
        ]);
    }

    /** Tracking khusus Iuran Sosial */
    public function trackingIuran(Request $request)
    {
        $wargaId = (int) session('warga_id');
        if (!$wargaId) {
            return redirect()->route('warga.login.form');
        }

        $warga = Warga::findOrFail($wargaId);

        // Pastikan warga yang login adalah KEPALA atau minimal punya id kepala
        // Jika data kamu: kepala keluarga ditandai kolom warga_id (parent) di tabel wargas,
        // maka kepala = warga_id NULL. Kalau yang login anggota, kepala_id = warga->warga_id.
        $kepalaId = $warga->warga_id ? (int) $warga->warga_id : (int) $warga->id;

        $tahun = (int) ($request->get('tahun', now()->year));
        $bulanMap = $this->bulanMap();

        $rows = IuranBulanan::query()
            ->where('warga_kepala_id', $kepalaId)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('bulan'); // 1..12

        $status = [];
        $totalBayar = 0;
        $totalTagihan = 0;

        foreach (range(1, 12) as $b) {
            $trx = $rows->get($b);

            $tagihan = (int) ($trx->nominal_tagihan ?? 0);
            $bayar = (int) ($trx->nominal_bayar ?? 0);
            $st = (string) ($trx->status ?? 'belum');

            $status[$b] = [
                'bulan_angka' => $b,
                'bulan_nama' => $bulanMap[$b],
                'nominal_tagihan' => $tagihan,
                'nominal_bayar' => $bayar,
                'status' => $st,                 // belum | sebagian | lunas
                'lunas' => ($st === 'lunas'),   // penentu utama
                'tanggal_bayar' => $trx?->tanggal_bayar,
                'metode_bayar' => $trx?->metode_bayar,
                'trx_id' => $trx?->id,
            ];

            $totalTagihan += $tagihan;
            $totalBayar += $bayar;
        }

        return view('bidang.sosial.iuran.tracking-iuran', [
            'warga' => $warga,
            'kepalaId' => $kepalaId,
            'tahun' => $tahun,
            'status' => $status,
            'bulanMap' => $bulanMap,
            'totalTagihan' => $totalTagihan,
            'totalBayar' => $totalBayar,
        ]);
    }

}
