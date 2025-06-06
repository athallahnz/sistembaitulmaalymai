<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\EduClass;
use App\Models\TagihanSpp;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TagihanSppExport;

class TagihanSppController extends Controller
{

    public function dashboardTagihan(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan');
        $kelasId = $request->get('kelas');

        // Ambil data kelas untuk filter
        $kelasList = EduClass::all();

        // Ambil semua siswa dengan relasi tagihan
        $students = Student::with([
            'eduClass',
            'tagihanSpps' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun);
                if ($bulan) {
                    $query->where('bulan', $bulan);
                }
            }
        ]);

        if ($kelasId) {
            $students->where('edu_class_id', $kelasId);
        }

        $data = $students->get()->map(function ($s) {
            $s->kelas = $s->eduClass->name ?? '-';
            $s->total_tagihan = $s->tagihanSpps->sum('jumlah');
            $s->total_bayar = $s->tagihanSpps->where('status', 'lunas')->sum('jumlah');
            return $s;
        });


        // Data chart: total tagihan dan pembayaran
        $chartLabels = $data->pluck('name');
        $chartTagihan = $data->pluck('total_tagihan');
        $chartPembayaran = $data->pluck('total_bayar');

        return view('bidang.pendidikan.payments.tagihan_spp.dashboard', compact(
            'tahun',
            'bulan',
            'kelasId',
            'kelasList',
            'data',
            'chartLabels',
            'chartTagihan',
            'chartPembayaran'
        ));
    }

    public function create()
    {
        $classes = EduClass::all();
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

    public function getData(Request $request)
    {
        $tahun = $request->tahun;
        $bulan = $request->bulan;
        $kelas = $request->kelas;

        $students = Student::with('eduClass');

        if ($kelas) {
            $students->where('edu_class_id', $kelas);
        }

        $students = $students->get()->map(function ($student) use ($tahun, $bulan) {
            $tagihanQuery = TagihanSpp::where('student_id', $student->id);
            $bayarQuery = TagihanSpp::where('student_id', $student->id)->where('status', 'lunas');

            if ($tahun) {
                $tagihanQuery->where('tahun', $tahun);
                $bayarQuery->where('tahun', $tahun);
            }

            if ($bulan) {
                $tagihanQuery->where('bulan', $bulan);
                $bayarQuery->where('bulan', $bulan);
            }

            $student->total_tagihan = $tagihanQuery->sum('jumlah');
            $student->total_bayar = $bayarQuery->sum('jumlah');

            return $student;
        });

        return DataTables::of($students)
            ->addColumn('kelas', function ($row) {
                return $row->eduClass->name ?? '-';
            })
            ->addColumn('status', function ($row) {
                if ($row->total_tagihan == 0) {
                    return 'belum_ada';
                } elseif ($row->total_bayar >= $row->total_tagihan) {
                    return 'lunas';
                } else {
                    return 'belum_lunas';
                }
            })
            ->addColumn('aksi', function ($row) {
                return $row->id; // Kirim ID untuk dipakai di JS
            })
            ->make(true);
    }

    public function show($id)
    {
        $student = Student::with('eduClass', 'tagihanSpps')->findOrFail($id);

        return view('bidang.pendidikan.payments.tagihan_spp.show', compact('student'));
    }

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

    public function getTagihanByRfid($uid)
    {
        $student = Student::where('rfid_uid', $uid)->first();

        if (!$student) {
            return response()->json(null, 404);
        }

        // Ambil total tagihan yang statusnya belum_lunas
        $totalTagihan = $student->tagihanSpps()
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        // Hitung sudah bayar, bisa dari tabel payments atau dari tagihan lunas (jika ada)
        // Contoh jika ada relasi payments:
        // $sudahBayar = $student->payments()->sum('jumlah');

        // Jika belum ada tabel pembayaran terpisah, bisa hitung total tagihan lunas
        $sudahBayar = $student->tagihanSpps()
            ->where('status', 'lunas')
            ->sum('jumlah');

        $sisa = $totalTagihan - $sudahBayar;

        // Jika sisa <= 0 artinya sudah lunas semua
        if ($totalTagihan <= 0) {
            return response()->json([
                'message' => 'Semua tagihan sudah lunas',
                'id' => $student->id,
                'name' => $student->name,
                'edu_class' => $student->edu_class ? $student->edu_class->name : null,
                'tahun_ajaran' => $student->edu_class ? $student->edu_class->tahun_ajaran : null,
                'total' => 0,
                'sisa' => 0,
            ], 200);
        }

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'edu_class' => $student->edu_class ? $student->edu_class->name : null,
            'tahun_ajaran' => $student->edu_class ? $student->edu_class->tahun_ajaran : null,
            'total' => $totalTagihan,
            'sisa' => $sisa > 0 ? $sisa : 0
        ]);
    }

    public function bayar(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'jumlah' => 'required|numeric|min:1',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Ambil tagihan spp yang belum lunas, urut dari yang paling lama
        $tagihan = $student->tagihanSpps()
            ->where('status', 'belum_lunas')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->first();

        if (!$tagihan) {
            return redirect()->back()->with('error', 'Tagihan tidak ditemukan.');
        }

        if ($request->jumlah < $tagihan->jumlah) {
            return redirect()->back()->with('error', 'Jumlah bayar kurang dari tagihan.');
        }

        // Update status tagihan jadi lunas
        $tagihan->update([
            'status' => 'lunas',
        ]);

        // Catat pembayaran (optional, jika ada tabel payments)
        // $student->payments()->create([
        //     'jumlah' => $request->jumlah,
        //     'tanggal_bayar' => now(),
        // ]);

        return redirect()->back()->with('success', 'Pembayaran berhasil!');
    }

    public function getChartBulanan(Request $request)
    {
        $tahun = $request->tahun;
        $kelas = $request->kelas;

        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $tagihanPerBulan = [];
        $pembayaranPerBulan = [];

        foreach ($bulanList as $num => $namaBulan) {
            $students = Student::query();

            if ($kelas) {
                $students->where('edu_class_id', $kelas);
            }

            $studentIds = $students->pluck('id');

            $totalTagihan = TagihanSpp::whereIn('student_id', $studentIds)
                ->where('tahun', $tahun)
                ->where('bulan', $num)
                ->sum('jumlah');

            $totalBayar = TagihanSpp::whereIn('student_id', $studentIds)
                ->where('tahun', $tahun)
                ->where('bulan', $num)
                ->where('status', 'lunas')
                ->sum('jumlah');

            $tagihanPerBulan[] = $totalTagihan;
            $pembayaranPerBulan[] = $totalBayar;
        }

        return response()->json([
            'labels' => array_values($bulanList),
            'tagihan' => $tagihanPerBulan,
            'pembayaran' => $pembayaranPerBulan
        ]);
    }

}
