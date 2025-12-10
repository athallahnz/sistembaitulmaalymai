<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Bidang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::all();
        return view('admin.users.index', compact('bidangs'));
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $users = User::withTrashed() // Menampilkan data yang sudah dihapus
                ->select('id', 'name', 'email', 'nomor', 'role', 'bidang_name', 'deleted_at', 'last_activity_at', 'is_active'); // Include 'last_activity_at'

            $data = DataTables::of($users)
                ->addColumn('bidang_name', function ($user) {
                    return $user->bidang->name ?? '-';
                })
                ->addColumn('status', function ($user) {
                    $lastActivity = $user->last_activity_at;

                    // Jika tidak pernah aktif
                    if (!$lastActivity) {
                        return '<span class="badge bg-secondary">Tidak pernah aktif</span>';
                    }

                    // Hitung durasi tidak aktif
                    $minutesSinceLastActivity = Carbon::now()->diffInMinutes($lastActivity);

                    if ($user->is_active && $minutesSinceLastActivity < 2) {
                        return '<div>
                                    <span class="badge bg-success">Online</span><br>
                                    <small class="text-muted">Aktif ' . $lastActivity->diffForHumans() . '</small>
                                </div>';
                    }

                    if ($user->is_active) {
                        return '<div>
                                    <span class="badge bg-warning text-dark">Idle</span><br>
                                    <small class="text-muted">Login tapi tidak aktif sejak ' . $lastActivity->diffForHumans() . '</small>
                                </div>';
                    }

                    return '<div>
                                <span class="badge bg-secondary">Offline</span><br>
                                <small class="text-muted">Logout ' . $lastActivity->diffForHumans() . '</small>
                            </div>';
                })

                ->addColumn('actions', function ($user) {
                    return view('admin.users.actions', compact('user'))->render();
                })
                ->editColumn('deleted_at', function ($user) {
                    return $user->deleted_at ? '<span class="text-danger">Terhapus</span>' : '<span class="text-success">Aktif</span>';
                })
                ->rawColumns(['actions', 'deleted_at', 'status']) // Include 'status' for raw HTML
                ->make(true);

            // Log for debugging
            \Log::info($data);

            return $data;
        }
    }

    public function create()
    {
        $bidangs = Bidang::all();
        return view('admin.users.create', compact('bidangs'));
    }

    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'nomor'       => 'required|unique:users,nomor',
            'pin'         => 'required|string|min:6',
            'role'        => 'required|in:Admin,User,Ketua Yayasan,Bendahara,Manajer Keuangan,Bidang',

            // wajib diisi kalau role = Bidang, dan nilainya harus ada di tabel bidangs.id
            'bidang_name' => 'nullable|required_if:role,Bidang|exists:bidangs,id',
        ]);

        $userData = [
            'name'  => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
            'pin'   => bcrypt($request->pin),
            'role'  => $request->role,
        ];

        // Kalau role Bidang → simpan ID bidang, kalau bukan → null
        if ($request->role === 'Bidang') {
            $userData['bidang_name'] = $request->bidang_name;
        } else {
            $userData['bidang_name'] = null;
        }

        // Buat user baru
        $user = User::create($userData);

        // Assign role (Spatie)
        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat');
    }

    public function edit(User $user)
    {
        $bidangs = Bidang::all();
        return view('admin.users.edit', compact('user', 'bidangs'));
    }


    public function update(Request $request, User $user)
    {
        // Validasi data
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'nomor'       => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'pin'         => ['nullable', 'string', 'min:4', 'max:6'],
            'role'        => 'required|in:Admin,User,Ketua Yayasan,Bendahara,Manajer Keuangan,Bidang',

            // wajib kalau role = Bidang, dan harus ID yang ada di bidangs
            'bidang_name' => 'nullable|required_if:role,Bidang|exists:bidangs,id',

            'foto'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $userData = [
            'name'  => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
            'pin'   => $request->pin ? Hash::make($request->pin) : $user->pin,
            'role'  => $request->role,
        ];

        // Role = Bidang → simpan ID, selain itu → null
        if ($request->role === 'Bidang') {
            $userData['bidang_name'] = $request->bidang_name;
        } else {
            $userData['bidang_name'] = null;
        }

        // (Optional) kalau mau proses upload foto di sini juga, tambahkan blok seperti di updateProfile()

        $user->update($userData);

        // Spatie role
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

    public function editProfile(Request $request)
    {
        $user = auth()->user(); // Ambil user yang sedang login
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            Log::info("Mulai update profil", ['user_id' => $user->id]);

            // Validasi form
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'nomor' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'pin' => ['nullable', 'string', 'min:4', 'max:6'],
                'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

        } catch (ValidationException $e) {

            // 🔥 Tangkap error khusus foto lebih dari 2MB
            if ($e->errors()['foto'][0] ?? false) {
                if (str_contains($e->errors()['foto'][0], 'kilobytes')) {
                    return back()->with('error', 'Ukuran foto maksimal 2 MB!')->withInput();
                }
            }

            // error lainnya -> default
            throw $e;
        }

        Log::info("Validasi berhasil");

        // =========== lanjut proses update, sama seperti kode kamu ===========
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'nomor' => $request->nomor,
        ];

        if ($request->filled('pin')) {
            $userData['pin'] = Hash::make($request->pin);
        }

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::exists('public/' . $user->foto)) {
                Storage::delete('public/' . $user->foto);
            }

            $file = $request->file('foto');
            $filename = 'foto_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/foto_user', $filename);
            $userData['foto'] = 'foto_user/' . $filename;
        }

        $user->update($userData);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }

}
