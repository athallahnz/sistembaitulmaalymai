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
        // Konfigurasi proteksi
        $MAX_ATTEMPTS = 5;   // berapa kali percobaan gagal
        $LOCKOUT_MINUTES = 10;  // berapa menit dikunci

        // ✅ Validasi khusus jika nomor kosong → SweetAlert
        if (!$request->filled('nomor')) {
            session()->flash('swal', [
                'icon' => 'warning',
                'title' => 'Nomor kosong!',
                'text' => 'Masukkan nomor terlebih dahulu.',
            ]);
            return redirect()->back()->withInput();
        }

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

        // ==============================
        // CEK AKUN TERKUNCI
        // ==============================
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $diffMinutes = $user->locked_until->diffInMinutes(now());
            session()->flash(
                'error',
                "Akun Anda dikunci sementara karena terlalu banyak percobaan login gagal. Coba lagi dalam {$diffMinutes} menit."
            );
            return redirect()->back();
        }

        // ==============================
        // PROSES LOGIN NORMAL
        // ==============================
        if ($user && Hash::check($request->pin, $user->pin)) {

            // ✅ Login sukses → reset counter gagal & unlock
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
                'last_activity_at' => now(),
                'is_active' => true,
            ]);

            Auth::login($user, true);

            session()->forget(['step', 'nomor']);
            session()->flash('login_success', 'Selamat datang, ' . $user->name . '.');

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

        // ==============================
        // LOGIN GAGAL → NAIKKAN COUNTER (BERTINGKAT)
        // ==============================
        if ($user) {
            $attempts = (int) $user->failed_login_attempts + 1;
            $user->failed_login_attempts = $attempts;

            // Hitung kelebihan percobaan di atas batas
            if ($attempts > $MAX_ATTEMPTS) {

                // Percobaan ke-6 → 10 menit
                // Percobaan ke-7 → 20 menit
                // Percobaan ke-8 → 30 menit
                $excessAttempts = $attempts - $MAX_ATTEMPTS;
                $lockMinutes = $excessAttempts * 10;

                $user->locked_until = now()->addMinutes($lockMinutes);
                $user->save();

                session()->flash(
                    'error',
                    "Akun Anda dikunci selama {$lockMinutes} menit karena terlalu banyak percobaan login gagal."
                );

                return redirect()->back();
            }

            // Masih di bawah batas
            $user->save();

            session()->flash(
                'error',
                "PIN salah! Percobaan ke-{$attempts} dari {$MAX_ATTEMPTS}."
            );
        } else {
            session()->flash('error', 'Nomor tidak terdaftar!');
        }

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

    public function resetPinAjax(Request $request)
    {
        $request->validate([
            'nomor' => 'required',
        ]);

        // Sesuaikan nama kolom nomor di tabel users: 'nomor', 'phone', 'no_hp', dll.
        $user = User::where('nomor', $request->nomor)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor tidak ditemukan dalam sistem.',
            ], 404);
        }

        // Generate PIN baru 6 digit
        $newPin = rand(100000, 999999);

        // ✅ Simpan PIN baru dalam bentuk Bcrypt (HASH)
        $user->pin = Hash::make($newPin);   // atau bcrypt($newPin)
        $user->save();

        // Buat teks untuk WA (user bisa kirim ke dirinya sendiri / catatan)
        $appName = config('app.name', 'Sistem Informasi Keuangan');
        $waText = "Assalamu'alaikum.\n"
            . "PIN login {$appName} Anda telah direset.\n\n"
            . "Nomor: {$user->nomor}\n"
            . "PIN Baru: {$newPin}\n\n"
            . "Mohon jaga kerahasiaan PIN ini.";

        // wa.me tanpa nomor → user pilih sendiri mau kirim ke siapa (termasuk ke catatan pribadi)
        $waUrl = 'https://wa.me/?text=' . urlencode($waText);

        return response()->json([
            'status' => 'ok',
            'message' => 'PIN baru berhasil dibuat.',
            'pin' => $newPin,
            'wa_url' => $waUrl,
        ]);
    }
}
