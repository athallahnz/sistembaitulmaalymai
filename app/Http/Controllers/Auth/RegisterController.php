<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;  // Pastikan Request diimpor
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /*
    |----------------------------------------------------------------------
    | Register Controller
    |----------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';  // Bisa tetap menggunakan ini, atau ganti dengan registered

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'nomor' => ['required', 'string', 'max:15'],
            'pin' => ['required', 'string', 'min:6', 'max:6'],
            'role' => ['required', 'exists:roles,name'], // Validasi role
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Buat user baru
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'nomor' => $data['nomor'], // Simpan nomor telepon
            'pin' => Hash::make($data['pin']), // Simpan PIN yang sudah dienkripsi
            'role' => $data['role'],
        ]);

        // Assign role berdasarkan pilihan dari dropdown
        $user->assignRole($data['role']);

        return $user;
    }

    /**
     * After user has been registered, determine where to redirect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function registered(Request $request, $user)
{
    // Redirect berdasarkan role user
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
            return redirect()->route('welcome'); // Default redirect jika role tidak dikenali
    }
}

}
