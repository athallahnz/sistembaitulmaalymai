<?php

namespace App\Http\Controllers\Pendidikan;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\EduClass;
use App\Models\AkunKeuangan;
use Yajra\DataTables\DataTables;


class EduClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kelasList = EduClass::withCount('students')->get();
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        $akunKeuangans = AkunKeuangan::where('parent_id', 202)->get();
        return view('bidang.pendidikan.kelas.index', compact('kelasList', 'eduClasses', 'akunKeuangans'));
    }

    public function data()
    {
        $data = EduClass::withCount('students')->get();

        return DataTables::of($data)
            ->addColumn('students_count', function ($row) {
                \Log::info($row); // cek di storage/logs/laravel.log
                return $row->students_count ?? 0;
            })
            ->addColumn('actions', function ($row) {
                $showUrl = route('edu_classes.show', $row->id);
                $editUrl = route('edu_classes.edit', $row->id);
                $deleteUrl = route('edu_classes.destroy', $row->id);

                $deleteButton = '';
                if ($row->students_count == 0) {
                    $deleteButton = '
            <button type="button" class="btn btn-danger btn-sm btn-delete" data-url="' . $deleteUrl . '" title="Hapus">
                <i class="bi bi-trash"></i>
            </button>
        ';
                }

                return '
        <a href="' . $showUrl . '" class="btn btn-info btn-sm" title="Lihat"><i class="bi bi-eye"></i></a>
        <a href="' . $editUrl . '" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
        ' . $deleteButton . '
    ';
            })
            ->rawColumns(['actions'])
            ->make(true);
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

        return redirect()->route('edu_classes.index')
            ->with('success', 'Kelas baru berhasil ditambahkan.');
    }


    /**
     * Display the specified resource.
     */
    public function show(EduClass $eduClass)
    {
        return view('bidang.pendidikan.kelas.show', compact('eduClass'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EduClass $eduClass)
    {
        return view('bidang.pendidikan.kelas.edit', compact('eduClass'));
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
        // Cek apakah kelas masih memiliki murid
        if ($eduClass->students()->count() > 0) {
            return redirect()->route('edu_classes.index')
                ->with('error', 'Kelas tidak dapat dihapus karena masih terdapat murid di dalamnya.');
        }

        // Jika tidak ada murid, hapus kelas
        $eduClass->delete();

        return redirect()->route('edu_classes.index')
            ->with('success', 'Data kelas berhasil dihapus.');
    }

}
