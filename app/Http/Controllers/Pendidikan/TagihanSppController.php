<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Services\StudentPaymentSPPService;
use App\Models\Student;
use App\Models\EduClass;
use App\Models\TagihanSpp;
use App\Exports\TagihanSppExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

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
                'tanggal_aktif' => \Carbon\Carbon::parse($request->tanggal_aktif),
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

    public function printReceipt($id)
    {
        $tagihan = TagihanSpp::with('student.eduClass')->findOrFail($id);
        $tahunAjaran = $tagihan->student->eduClass->tahun_ajaran;
        $nomorInduk = $tagihan->student->no_induk;
        $logo = public_path('img/photos/logo_yys.png');

        if ($tagihan->status !== 'lunas') {
            abort(403, 'Kwitansi hanya tersedia untuk tagihan yang lunas.');
        }

        $nomorKwitansi = 'SPP/' . $tahunAjaran . '/' . $nomorInduk . '/' . str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT);
        $keterangan = 'Pembayaran SPP bulan ' . \Carbon\Carbon::create()->month($tagihan->bulan)->translatedFormat('F');
        $urlVerifikasi = route('spp.verifikasi', $tagihan->id);

        $filename = 'spp_' . $tagihan->id . '.svg';
        $qrPath = storage_path('app/public/qrcodes/' . $filename); // path fisik

        // Pastikan foldernya ada
        if (!file_exists(dirname($qrPath))) {
            mkdir(dirname($qrPath), 0755, true);
        }
        file_put_contents($qrPath, QrCode::format('svg')->size(100)->generate($urlVerifikasi));

        $tahunAjaranBersih = str_replace(['/', '\\'], '-', $tahunAjaran);
        $namaSiswaBersih = preg_replace('/[^A-Za-z0-9\-]/', '_', $tagihan->student->name);
        $namaFile = 'SPP-' . $tahunAjaranBersih . '-' . $nomorInduk . str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT) . '-' . $namaSiswaBersih . '.pdf';

        // Versi HTML untuk cetak langsung atau PDF
        $pdf = Pdf::loadView('bidang.pendidikan.payments.tagihan_spp.kwitansi-per-pembayaran', compact(
            'tagihan',
            'nomorKwitansi',
            'keterangan',
            'qrPath',
            'logo'
        ))->setPaper([0, 0, 227, 600]); // 80mm x ±210mm

        return $pdf->stream($namaFile);
    }
}
