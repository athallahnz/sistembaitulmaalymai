<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $users = User::withTrashed() // Menampilkan data yang sudah dihapus
                ->select('id', 'name', 'email', 'nomor', 'role', 'bidang_name', 'deleted_at'); // Tidak perlu join

            $data = DataTables::of($users)
                ->addColumn('bidang_name', function ($user) {
                    return $user->role == 'Bidang' ? $user->bidang_name : '-';
                })
                ->addColumn('actions', function ($user) {
                    return view('admin.users.actions', compact('user'))->render();
                })
                ->editColumn('deleted_at', function ($user) {
                    return $user->deleted_at ? '<span class="text-danger">Terhapus</span>' : '<span class="text-success">Aktif</span>';
                })
                ->rawColumns(['actions', 'deleted_at'])
                ->make(true);

            // Log untuk memastikan data sudah sampai
            \Log::info($data);

            return $data;
        }
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nomor' => 'required|unique:users,nomor',
            'pin' => 'required|string|min:6',  // Sesuaikan dengan panjang PIN yang Anda tentukan
            'role' => 'required|in:Admin,User,Ketua Yayasan,Bendahara,Manajer Keuangan,Bidang',
            'bidang_name' => 'nullable|string',  // Kolom bidang_name hanya diperlukan jika role adalah "Bidang"
        ]);

        // Menentukan data yang akan disimpan
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
            'pin' => bcrypt($request->pin),
            'role' => $request->role,
        ];

        // Jika role adalah Bidang, tambahkan bidang_name ke dalam data
        if ($request->role === 'Bidang') {
            $userData['bidang_name'] = $request->bidang_name;
        }

        // Buat user baru
        $user = User::create($userData);

        // Jika role adalah Bidang dan bidang_name disediakan, perbarui bidang_name
        if ($request->role === 'Bidang' && $request->has('bidang_name')) {
            $user->update(['bidang_name' => $request->bidang_name]);
        }

        // Assign role menggunakan spatie
        $user->assignRole($request->role);

        // dd($request->all());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'nomor' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'],  // Pin optional, bisa dikosongkan
            'role' => 'required|in:Admin,User,Ketua Yayasan,Bendahara,Manajer Keuangan,Bidang',
            'bidang_name' => 'nullable|string',  // Pastikan bidang_name tetap dapat diterima saat update
        ]);

        // Update data user
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
            'pin' => $request->pin ? Hash::make($request->pin) : $user->pin,  // Jika pin tidak diubah, tetap menggunakan pin lama
            'role' => $request->role,
        ];

        // Jika role adalah "Bidang", update bidang_name
        if ($request->role === 'Bidang' && $request->has('bidang_name')) {
            $userData['bidang_name'] = $request->bidang_name;
        }

        // Update user
        $user->update($userData);

        // Assign role menggunakan spatie jika diperlukan
        $user->syncRoles($request->role);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui!');
    }


    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Cek apakah user yang dihapus adalah admin utama (opsional)
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.users.index')->with('error', 'Admin utama tidak bisa dihapus!');
            }

            $user->delete(); // Hanya menandai user sebagai "terhapus"

            return redirect()->route('admin.users.index')->with('success', 'User berhasil dinonaktifkan!');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.index')->with(['success' => 'User berhasil dipulihkan!']);
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete(); // Hapus permanen

        return redirect()->route('admin.users.index')->with(['success' => 'User dihapus permanen!']);
    }

    public function editProfile()
    {
        $user = auth()->user(); // Ambil user yang sedang login
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user(); // Ambil user yang sedang login

        // Validasi umum untuk semua user
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'nomor' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'], // Optional, hanya jika ingin mengubah
        ];

        // Validasi bidang_name hanya untuk role "Bidang", tetapi abaikan perubahan pada server
        if ($user->role === 'Bidang') {
            $rules['bidang_name'] = 'required|string|max:255';
        }

        // Validasi request
        $request->validate($rules);

        // Data yang akan diupdate
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
        ];

        // Update PIN jika diberikan
        if ($request->filled('pin')) {
            $userData['pin'] = Hash::make($request->pin);
        }

        // Abaikan perubahan bidang_name pada server
        // Tidak perlu mengupdate bidang_name karena harus tetap sama seperti sebelumnya

        // Update user data
        $user->update($userData);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }

}
