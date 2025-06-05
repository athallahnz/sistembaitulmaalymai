<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\EduClass;
use App\Models\TagihanSpp;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TagihanSppExport;

class TagihanSppController extends Controller
{

    public function dashboardTagihan(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $bulan = $request->bulan;
        $kelasId = $request->kelas;

        $students = Student::with('eduClass')
            ->when($kelasId, fn($q) => $q->where('edu_class_id', $kelasId))
            ->get();

        $data = $students->map(function ($student) use ($tahun, $bulan) {
            $tagihanQuery = $student->tagihanSpps()
                ->where('tahun', $tahun);

            if ($bulan) {
                $tagihanQuery->where('bulan', $bulan);
            }

            $tagihan = $tagihanQuery->get();

            $total_tagihan = $tagihan->sum('jumlah');
            $total_bayar = $tagihan->where('status', 'lunas')->sum('jumlah');

            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'kelas' => $student->eduClass->name ?? '-',
                'total_tagihan' => $total_tagihan,
                'total_bayar' => $total_bayar,
            ];
        });

        $kelasList = EduClass::all();

        return view('bidang.pendidikan.payments.tagihan_spp.dashboard', compact('data', 'tahun', 'bulan', 'kelasId', 'kelasList'));
    }

    public function create()
    {
        $classes = \App\Models\EduClass::all();
        return view('bidang.pendidikan.payments.tagihan_spp.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'edu_class_ids' => 'required|array',
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric|min:2020',
            'jumlah' => 'required|numeric|min:1000',
            'tanggal_aktif' => 'required|date',
        ]);

        $students = Student::whereIn('edu_class_id', $request->edu_class_ids)->get();

        foreach ($students as $student) {
            TagihanSpp::updateOrCreate([
                'student_id' => $student->id,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
            ], [
                'jumlah' => $request->jumlah,
                'status' => 'belum_lunas',
                'tanggal_aktif' => \Carbon\Carbon::parse($request->tanggal_aktif),
            ]);
        }

        return redirect()->back()->with('success', 'Tagihan berhasil dibuat untuk semua siswa.');
    }

    // Export Excel
    public function export(Request $request)
    {
        $request->validate([
            'tahun' => 'required|numeric',
            'bulan' => 'required|numeric|between:1,12',
            'edu_class_ids' => 'required|array|min:1',
            'edu_class_ids.*' => 'exists:edu_classes,id',
        ]);

        return Excel::download(
            new TagihanSppExport($request->tahun, $request->bulan, $request->edu_class_ids),
            "tagihan_spp_{$request->bulan}_{$request->tahun}.xlsx"
        );
    }

}
