<?php

namespace App\Http\Controllers\Pendidikan;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;// pastikan model sudah ada dan sesuai
use App\Models\EduClass;
use App\Models\AkunKeuangan;

class EduClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $eduClasses = EduClass::orderBy('tahun_awal', 'desc')->get();
        $akunKeuangan = AkunKeuangan::where('parent_id', 202)->get();
        return view('edu_classes.index', compact('eduClasses','akunKeuangan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('edu_classes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_awal' => 'required|integer|min:2000|max:2099',
            'akun_keuangan_ids' => 'nullable|array',
            'akun_keuangan_ids.*' => 'exists:akun_keuangans,id',
        ]);

        // Buat format tahun_ajaran, misal 2025/2026
        $tahunAjaran = $validated['tahun_awal'] . '/' . ($validated['tahun_awal'] + 1);

        // Simpan kelas
        $eduClass = EduClass::create([
            'name' => $validated['name'],
            'tahun_awal' => $validated['tahun_awal'],
            'tahun_ajaran' => $tahunAjaran,
        ]);

        // Simpan relasi ke pivot table (jika ada akun keuangan yang dipilih)
        if ($request->has('akun_keuangan_ids')) {
            $eduClass->akunKeuangans()->sync($validated['akun_keuangan_ids']);
        }

        return redirect()->route('students.index')
            ->with('success', 'Kelas baru berhasil ditambahkan.');
    }


    /**
     * Display the specified resource.
     */
    public function show(EduClass $eduClass)
    {
        return view('edu_classes.show', compact('eduClass'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EduClass $eduClass)
    {
        return view('edu_classes.edit', compact('eduClass'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EduClass $eduClass)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_awal' => 'required|integer|min:2000|max:2099',
        ]);

        $tahunAjaran = $validated['tahun_awal'] . '/' . ($validated['tahun_awal'] + 1);

        $eduClass->update([
            'name' => $validated['name'],
            'tahun_awal' => $validated['tahun_awal'],
            'tahun_ajaran' => $tahunAjaran,
        ]);

        return redirect()->route('edu_classes.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EduClass $eduClass)
    {
        $eduClass->delete();

        return redirect()->route('edu_classes.index')
            ->with('success', 'Data kelas berhasil dihapus.');
    }
}
