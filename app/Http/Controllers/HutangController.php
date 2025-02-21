<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hutang;
use App\Models\User;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;

class HutangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hutangs = Hutang::with('user', 'akunKeuangan')->get();
        return view('hutang.index', compact('hutangs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun piutang dengan ID 103
        $akunHutang = AkunKeuangan::where('id', 201)->first();

        // Ambil akun yang memiliki parent_id = 103
        $parentAkunHutang = AkunKeuangan::where('parent_id', 201)->get();

        // Ambil bidang_name selain yang login
        // Ambil bidang_name selain yang login
        $bidang_name = auth()->user()->bidang_name;

        // Ambil user yang memiliki bidang berbeda dari user yang login & memiliki role "Bendahara" atau "Bidang"
        $users = User::where('bidang_name', '!=', $bidang_name) // Hanya user dengan bidang berbeda
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'Bendahara'); // User dengan role Bendahara
            })
            ->get();

        return view('hutang.create', compact('akunKeuangan', 'akunHutang', 'parentAkunHutang', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'parent_akun_id' => 'nullable|exists:akun_keuangans,id', // Validasi akun parent (nullable)
            'jumlah' => 'required|numeric|min:0',
            'tanggal_jatuh_tempo' => 'required|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas',
        ]);

        // Cek apakah parent_akun_id ada dalam input
        $parentAkunId = $request->parent_akun_id ? $request->parent_akun_id : null;

        Hutang::create([
            'user_id' => $validated['user_id'],
            'akun_keuangan_id' => $validated['akun_keuangan_id'],
            'parent_id' => $parentAkunId, // Menyimpan parent_akun_id ke database
            'jumlah' => $validated['jumlah'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'deskripsi' => $validated['deskripsi'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hutang $hutang)
    {
        return view('hutang.show', compact('hutang'));
    }

    public function edit(Hutang $hutang)
    {
        $users = User::all();
        $akunKeuangans = AkunKeuangan::where('parent_id', 103)->get(); // Hanya akun hutang
        return view('hutang.edit', compact('hutang', 'users', 'akunKeuangans'));
    }

    public function update(Request $request, Hutang $hutang)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'jumlah' => 'required|numeric',
            'tanggal_jatuh_tempo' => 'required|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas',
        ]);

        $hutang->update($request->all());
        return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil diperbarui.');
    }

    public function destroy(Hutang $hutang)
    {
        $hutang->delete();
        return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil dihapus.');
    }
}
