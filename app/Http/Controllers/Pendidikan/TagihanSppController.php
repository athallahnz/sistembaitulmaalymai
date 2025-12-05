<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Exports\TagihanSppExport;
use App\Models\EduClass;
use App\Models\Student;
use App\Models\TagihanSpp;
use App\Models\SidebarSetting;
use App\Services\RevenueRecognitionService;
use App\Services\StudentPaymentSPPService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

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
        $classes = EduClass::orderBy('name')->orderBy('tahun_ajaran', 'desc')->get();
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
                'tanggal_aktif' => Carbon::parse($request->tanggal_aktif),
            ]);

            app(\App\Services\StudentFinanceService::class)->handleNewStudentSPPFinance($student, $request->jumlah, $request->bulan, $request->tahun, );
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
            ->addColumn('status', function ($row): string {
                if ($row->total_tagihan > 1) {
                    if ($row->total_bayar >= $row->total_tagihan) {
                        return 'lunas';
                    } else {
                        return 'belum_lunas';
                    }
                } else {
                    return 'belum_ada';
                }
            })
            ->addColumn('aksi', function ($row) {
                return $row->id;
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
            'metode' => 'required|in:tunai,transfer'
        ]);

        $student = Student::findOrFail($request->student_id);

        StudentPaymentSPPService::recordPayment($student, $request->jumlah, $request->metode);

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
        $tahun = $request->input('tahun', date('Y'));
        $kelas = $request->input('kelas');

        \Log::info('Chart filter', [
            'tahun' => $tahun,
            'kelas' => $kelas,
            'count_tahun' => TagihanSpp::where('tahun', $tahun)->count(),
            'count_2026' => TagihanSpp::where('tahun', 2026)->count(),
        ]);

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

        $studentsQuery = Student::query();
        if ($kelas) {
            $studentsQuery->where('edu_class_id', $kelas);
        }
        $studentIds = $studentsQuery->pluck('id');

        foreach ($bulanList as $num => $namaBulan) {
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

    public function printReceipt($id)
    {
        $tagihan = TagihanSpp::with('student.eduClass')->findOrFail($id);

        if ($tagihan->status !== 'lunas') {
            abort(403, 'Kwitansi hanya tersedia untuk tagihan yang lunas.');
        }

        $setting = SidebarSetting::first();

        // Logo: ambil dari SidebarSetting kalau ada, kalau tidak pakai default
        $logo = public_path(
            $setting && $setting->logo_path
            ? 'storage/' . $setting->logo_path
            : 'img/photos/logo_yys.png'
        );

        $tahunAjaran = $tagihan->student->eduClass->tahun_ajaran ?? '-';
        $nomorInduk = $tagihan->student->no_induk ?? '-';

        $nomorKwitansi = 'SPP/' . $tahunAjaran . '/' . $nomorInduk . '/' .
            str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT);

        $keterangan = 'Pembayaran SPP bulan ' .
            Carbon::create()->month($tagihan->bulan)->translatedFormat('F');

        $tahunAjaranBersih = str_replace(['/', '\\'], '-', $tahunAjaran);
        $namaSiswaBersih = preg_replace('/[^A-Za-z0-9\-]/', '_', $tagihan->student->name);

        $namaFile = 'SPP-' . $tahunAjaranBersih . '-' .
            $nomorInduk . str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT) .
            '-' . $namaSiswaBersih . '.pdf';

        // 58mm slip → ±164pt, tinggi 600pt (cukup untuk kwitansi)
        $paperSize = [0, 0, 250, 400];

        $pdf = Pdf::loadView(
            'bidang.pendidikan.payments.tagihan_spp.kwitansi-per-pembayaran',
            [
                'tagihan' => $tagihan,
                'nomorKwitansi' => $nomorKwitansi,
                'keterangan' => $keterangan,
                'logo' => $logo,
            ]
        )->setPaper($paperSize, 'portrait');

        return $pdf->stream($namaFile);
    }

    public function recognizeStudentSPP(Request $request, Student $student)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Cek dulu: apakah siswa ini punya tagihan SPP LUNAS di bulan/tahun tsb?
        $tagihan = TagihanSpp::where('student_id', $student->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'lunas')
            ->first();

        if (!$tagihan) {
            return back()->with(
                'error',
                "Tidak ada tagihan SPP dengan status LUNAS untuk {$student->name} pada bulan {$bulan}/{$tahun}. Pengakuan pendapatan dibatalkan."
            );
        }

        // Kalau lolos, baru lakukan pengakuan pendapatan
        RevenueRecognitionService::recognizeSPP($student, $bulan, $tahun);

        return back()->with(
            'success',
            "Pengakuan pendapatan SPP {$student->name} bulan {$bulan}/{$tahun} berhasil."
        );
    }

    public function recognizeSPPBulk(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Ambil semua siswa yang PUNYA tagihan SPP LUNAS di bulan/tahun itu
        $studentIds = TagihanSpp::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'lunas')
            ->pluck('student_id')
            ->unique();

        // ⛔ Jika tidak ada student yang lunas → gagalkan
        if ($studentIds->isEmpty()) {
            return back()->with(
                'error',
                "Tidak ada siswa dengan tagihan SPP LUNAS pada bulan {$bulan}/{$tahun}. Tidak ada pengakuan pendapatan yang diproses."
            );
        }

        DB::transaction(function () use ($studentIds, $bulan, $tahun) {
            $students = Student::whereIn('id', $studentIds)->get();

            foreach ($students as $student) {
                RevenueRecognitionService::recognizeMonthlySPP(
                    $student,
                    $bulan,
                    $tahun
                );
            }
        });

        return back()->with(
            'success',
            "Pengakuan pendapatan SPP BULK bulan {$bulan}/{$tahun} berhasil diproses untuk {$studentIds->count()} siswa (status lunas)."
        );
    }

}
