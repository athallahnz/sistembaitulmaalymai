<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Piutang;
use App\Models\User;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;

class PiutangController extends Controller
{
    public function index()
    {
        $piutangs = Piutang::with('user', 'akunKeuangan')->get();
        return view('piutang.index', compact('piutangs'));
    }

    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun piutang dengan ID 103
        $akunPiutang = AkunKeuangan::where('id', 103)->first();

        // Ambil akun yang memiliki parent_id = 103
        $parentAkunPiutang = AkunKeuangan::where('parent_id', 103)->get();

        // Ambil bidang_name selain yang login
        // Ambil bidang_name selain yang login
        $bidang_name = auth()->user()->bidang_name;

        // Ambil user yang memiliki bidang berbeda dari user yang login & memiliki role "Bendahara" atau "Bidang"
        $users = User::where('bidang_name', '!=', $bidang_name) // Hanya user dengan bidang berbeda
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'Bendahara'); // User dengan role Bendahara
            })
            ->get();

        return view('piutang.create', compact('akunKeuangan', 'akunPiutang', 'parentAkunPiutang', 'users'));
    }

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

        Piutang::create([
            'user_id' => $validated['user_id'],
            'akun_keuangan_id' => $validated['akun_keuangan_id'],
            'parent_id' => $parentAkunId, // Menyimpan parent_akun_id ke database
            'jumlah' => $validated['jumlah'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'deskripsi' => $validated['deskripsi'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil ditambahkan.');
    }

    public function show(Piutang $piutang)
    {
        return view('piutang.show', compact('piutang'));
    }

    public function edit(Piutang $piutang)
    {
        $users = User::all();
        $akunKeuangans = AkunKeuangan::where('parent_id', 103)->get(); // Hanya akun piutang
        return view('piutang.edit', compact('piutang', 'users', 'akunKeuangans'));
    }

    public function update(Request $request, Piutang $piutang)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'jumlah' => 'required|numeric',
            'tanggal_jatuh_tempo' => 'required|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas',
        ]);

        $piutang->update($request->all());
        return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil diperbarui.');
    }

    public function destroy(Piutang $piutang)
    {
        $piutang->delete();
        return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil dihapus.');
    }
}
