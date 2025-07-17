<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class LoginController extends Controller
{
    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function authenticated(Request $request, $user)
    {
        // Update 'last_activity_at' dan 'last_login_at' sekaligus
        $user->update([
            'last_activity_at' => Carbon::now(),
            'last_login_at' => Carbon::now(),
            'is_active' => true, // ⬅ Tambahkan ini
        ]);
    }


    public function showLoginForm()
    {
        // Jika bukan sedang input PIN, reset sesi
        if (!session('step')) {
            session()->forget(['step', 'nomor']);
        }

        return view('auth.login');
    }

    public function verifyNomor(Request $request)
    {
        $request->validate([
            'nomor' => 'required|string|max:15'
        ]);

        $user = User::where('nomor', $request->nomor)->first();

        if (!$user) {
            return back()->with('error', 'Nomor tidak terdaftar!');
        }

        // Simpan ke session agar lanjut ke input PIN
        session([
            'step' => 'pin',
            'nomor' => $user->nomor,
        ]);

        return redirect()->route('login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor' => ['required', 'string', 'max:15'],
            'pin' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        if ($validator->fails()) {
            $errorMessage = '';

            if ($validator->errors()->has('nomor')) {
                $errorMessage .= 'Nomor tidak valid! ';
            }

            if ($validator->errors()->has('pin')) {
                $errorMessage .= 'PIN harus terdiri dari 6 angka!';
            }

            session()->flash('error', trim($errorMessage));
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::where('nomor', $request->nomor)->first();

        if ($user && Hash::check($request->pin, $user->pin)) {
            Auth::login($user, true);

            // ✅ Tambahkan update status login dan aktivitas di sini
            $user->update([
                'last_login_at' => now(),
                'last_activity_at' => Carbon::now(),
                'is_active' => true,
            ]);

            session()->forget(['step', 'nomor']); // Hapus sesi setelah login
            session()->flash('login success', 'Selamat datang, ' . $user->name . '.');

            switch ($user->role) {
                case 'Admin':
                    return redirect()->route('admin.index');
                case 'Ketua Yayasan':
                    return redirect()->route('ketua.index');
                case 'Bendahara':
                    return redirect()->route('bendahara.index');
                case 'Manajer Keuangan':
                    return redirect()->route('manajer.index');
                case 'Bidang':
                    return redirect()->route('bidang.index');
                default:
                    return redirect()->route('welcome');
            }
        }

        session()->flash('error', $user ? 'PIN salah!' : 'Nomor tidak terdaftar!');
        return redirect()->back();
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user->update(['is_active' => false]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // session()->flash('success', 'Anda telah logout.');
        return redirect()->route('login');
    }
}
