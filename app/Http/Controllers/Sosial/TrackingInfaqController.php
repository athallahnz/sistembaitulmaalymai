<?php

namespace App\Http\Controllers\Sosial;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Models\InfaqSosial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrackingInfaqController extends Controller
{
    public function showLogin()
    {
        return view('bidang.sosial.infaq.tracking-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'hp'  => ['required','string'],
            'pin' => ['required','string','min:4','max:16'],
        ]);

        $warga = Warga::with('infaq')->where('hp', $request->hp)->first();

        if (!$warga || !$warga->pin || !Hash::check($request->pin, $warga->pin)) {
            return back()->withInput()->with('error', 'Nomor HP atau PIN tidak valid.');
        }

        session(['warga_id' => $warga->id]);

        return redirect()->route('warga.dashboard');
    }

    public function logout()
    {
        session()->forget('warga_id');
        return redirect()->route('warga.login.form')->with('success', 'Anda telah keluar.');
    }

    public function dashboard()
    {
        $warga = Warga::with('infaq')->findOrFail(session('warga_id'));
        $infaq = $warga->infaq;
        $bulanList = InfaqSosial::monthColumns();

        $status = [];
        $total  = 0;
        foreach ($bulanList as $b) {
            $nom = (float)($infaq->$b ?? 0);
            $status[$b] = [
                'nominal' => $nom,
                'lunas'   => $nom > 0,
            ];
            $total += $nom;
        }

        return view('bidang.sosial.infaq.tracking-infaq', [
            'warga'     => $warga,
            'status'    => $status,
            'bulanList' => $bulanList,
            'total'     => $total,
        ]);
    }
}
