<?php

namespace App\Http\Controllers\Pendidikan;

use App\Models\AkunKeuangan;
use App\Models\EduClass;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EduClassController extends Controller
{
    public function index()
    {
        $akunKeuangans = AkunKeuangan::whereIn('parent_id', [201, 208])
            ->orderBy('kode_akun')
            ->get();

        // Dropdown tahun: (tahun ini - 1) s/d (tahun ini + 5)
        $currentYear = (int) date('Y');
        $years = range($currentYear - 1, $currentYear + 5);
        rsort($years);

        return view('bidang.pendidikan.kelas.index', compact('akunKeuangans', 'years'));
    }

    public function data()
    {
        $q = EduClass::query()
            ->select('edu_classes.id', 'edu_classes.name', 'edu_classes.tahun_ajaran')
            ->withCount('students')
            ->orderBy('tahun_ajaran', 'desc')
            ->orderBy('name');

        return DataTables::of($q)
            ->addColumn('students_count', fn($row) => (int) ($row->students_count ?? 0))
            ->addColumn('actions', function ($row) {
                $showPageUrl = route('edu_classes.show', $row->id) . '?view=1'; // HTML
                $editJsonUrl = route('edu_classes.show', $row->id);            // JSON
                $updateUrl   = route('edu_classes.update', $row->id);
                $deleteUrl   = route('edu_classes.destroy', $row->id);

                $btnShow = '
        <a href="' . $showPageUrl . '" class="btn btn-info btn-sm" title="Lihat">
            <i class="bi bi-eye"></i>
        </a>
    ';

                $btnEdit = '
        <button type="button"
            class="btn btn-warning btn-sm btn-edit"
            title="Edit"
            data-json-url="' . $editJsonUrl . '"
            data-update-url="' . $updateUrl . '">
            <i class="bi bi-pencil-square"></i>
        </button>
    ';

                $btnDelete = '';
                if ((int) $row->students_count === 0) {
                    $btnDelete = '
            <button type="button"
                class="btn btn-danger btn-sm btn-delete"
                title="Hapus"
                data-url="' . $deleteUrl . '">
                <i class="bi bi-trash"></i>
            </button>
        ';
                }

                return '<div class="d-flex gap-1">' . $btnShow . $btnEdit . $btnDelete . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_awal' => 'required|integer|min:2000|max:2099',
            'akun_keuangan_ids' => 'nullable|array',
            'akun_keuangan_ids.*' => 'exists:akun_keuangans,id',
        ]);

        $tahunAjaran = $validated['tahun_awal'] . '/' . ($validated['tahun_awal'] + 1);

        // cegah duplikasi berdasarkan (name + tahun_ajaran)
        $exists = EduClass::where('name', $validated['name'])
            ->where('tahun_ajaran', $tahunAjaran)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Kelas dengan nama tersebut sudah ada pada tahun ajaran ini.',
            ]);
        }

        DB::transaction(function () use ($validated, $tahunAjaran) {
            $eduClass = EduClass::create([
                'name' => $validated['name'],
                'tahun_ajaran' => $tahunAjaran,
            ]);

            $eduClass->akunKeuangans()->sync($validated['akun_keuangan_ids'] ?? []);
        });

        return redirect()->route('edu_classes.index')
            ->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    /**
     * JSON untuk modal edit
     */
    public function show(Request $request, EduClass $eduClass)
    {
        // load relasi yang dibutuhkan
        $eduClass->load(['akunKeuangans', 'students']);

        // derive tahun_awal dari "2025/2026"
        $tahunAwal = null;
        if (!empty($eduClass->tahun_ajaran) && str_contains($eduClass->tahun_ajaran, '/')) {
            $tahunAwal = (int) explode('/', $eduClass->tahun_ajaran)[0];
        }

        // Jika dipanggil untuk halaman (tombol show)
        if ($request->boolean('view')) {
            return view('bidang.pendidikan.kelas.show', compact('eduClass', 'tahunAwal'));
        }

        // Default: JSON untuk modal edit
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $eduClass->id,
                'name' => $eduClass->name,
                'tahun_awal' => $tahunAwal,
                'tahun_ajaran' => $eduClass->tahun_ajaran,
                'akun_keuangan_ids' => $eduClass->akunKeuangans->pluck('id')->map(fn($v) => (int)$v)->values(),
            ],
        ]);
    }


    public function update(Request $request, EduClass $eduClass)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_awal' => 'required|integer|min:2000|max:2099',
            'akun_keuangan_ids' => 'nullable|array',
            'akun_keuangan_ids.*' => 'exists:akun_keuangans,id',
        ]);

        $tahunAjaran = $validated['tahun_awal'] . '/' . ($validated['tahun_awal'] + 1);

        // cegah duplikasi berdasarkan (name + tahun_ajaran) kecuali id sendiri
        $exists = EduClass::where('id', '!=', $eduClass->id)
            ->where('name', $validated['name'])
            ->where('tahun_ajaran', $tahunAjaran)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Kelas dengan nama tersebut sudah ada pada tahun ajaran ini.',
            ]);
        }

        DB::transaction(function () use ($eduClass, $validated, $tahunAjaran) {
            $eduClass->update([
                'name' => $validated['name'],
                'tahun_ajaran' => $tahunAjaran,
            ]);

            $eduClass->akunKeuangans()->sync($validated['akun_keuangan_ids'] ?? []);
        });

        return redirect()->route('edu_classes.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(EduClass $eduClass)
    {
        if ($eduClass->students()->exists()) {
            return redirect()->route('edu_classes.index')
                ->with('error', 'Kelas tidak dapat dihapus karena masih terdapat murid di dalamnya.');
        }

        $eduClass->akunKeuangans()->detach();
        $eduClass->delete();

        return redirect()->route('edu_classes.index')
            ->with('success', 'Data kelas berhasil dihapus.');
    }
}
