<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\EduPayment;
use App\Models\Piutang;
use App\Models\Student;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\EduClass;
use App\Services\RevenueRecognitionService;
use App\Services\StudentPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class EduPaymentController extends Controller
{
    public function index()
    {
        // Gabungkan student + total pembayaran
        $data = DB::table('students')
            ->join('edu_payments', 'students.id', '=', 'edu_payments.student_id')
            ->select(
                'students.id',
                'students.name',
                DB::raw('SUM(edu_payments.jumlah) as total_bayar')
            )
            ->groupBy('students.id', 'students.name')
            ->get();

        // Data tren pembayaran per bulan (12 bulan terakhir)
        $monthlyTren = DB::table('edu_payments')
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->take(12)
            ->get();

        $labels = $monthlyTren->pluck('bulan');
        $values = $monthlyTren->pluck('total');
        $kelasList = EduClass::all();


        return view('bidang.pendidikan.payments.dashboard', compact('data', 'labels', 'values', 'kelasList'));
    }

    public function show(Student $student)
    {
        // Total biaya dari student_costs
        $totalBiaya = (float) DB::table('student_costs')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        // Total pembayaran dari edu_payments
        $totalBayar = (float) DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        // Sisa tanggungan (jika Anda ingin tetap "biaya - bayar")
        $sisa = $totalBiaya - $totalBayar;

        // Total yang sudah diakui pendapatan (berdasarkan ledger credit)
        $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb'); // 50012

        $totalRecognized = (float) Ledger::query()
            ->join('transaksis', 'transaksis.id', '=', 'ledgers.transaksi_id')
            ->where('transaksis.type', 'pengakuan_pendapatan')
            ->where('transaksis.student_id', $student->id)
            ->where('transaksis.akun_keuangan_id', $akunPBDPMB) // ✅ PMB-only
            ->sum('ledgers.credit');

        // (Opsional tapi sangat direkomendasikan)
        // Nominal yang masih bisa diakui sekarang = min(totalBayar, totalBiaya) - totalRecognized
        $capPaid = min($totalBayar, $totalBiaya);
        $remainingRecognizable = round($capPaid - $totalRecognized, 2);
        $remainingRecognizable = max(0, $remainingRecognizable);

        // Riwayat pembayaran
        $payments = DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('bidang.pendidikan.payments.detail', [
            'student' => $student,
            'totalBiaya' => $totalBiaya,
            'totalBayar' => $totalBayar,
            'sisa' => $sisa,
            'totalRecognized' => $totalRecognized,
            'remainingRecognizable' => $remainingRecognizable, // opsional
            'payments' => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'jumlah' => 'required|numeric|min:1000',
            'metode' => 'required|in:tunai,transfer',
        ]);

        $student = Student::with('biaya')->findOrFail($request->student_id);

        // Total biaya PMB (sum student_costs)
        $totalBiaya = (int) $student->biaya->sum('jumlah');

        // Total bayar PMB dihitung dari edu_payment_items (bill_type=pmb)
        $totalBayar = (int) \App\Models\EduPaymentItem::query()
            ->where('bill_type', 'pmb')
            ->whereIn('bill_id', $student->biaya->pluck('id'))
            ->sum('amount');

        $sisa = $totalBiaya - $totalBayar;

        if ($sisa <= 0) {
            return back()->with('error', 'Pembayaran ditolak: PMB siswa sudah lunas.');
        }

        $jumlahBayar = (int) $request->jumlah;
        if ($jumlahBayar > $sisa) {
            return back()->with('error', "Pembayaran melebihi sisa tagihan Rp " . number_format($sisa, 0, ',', '.'));
        }

        DB::transaction(function () use ($student, $jumlahBayar, $request) {
            StudentPaymentService::payPMB(
                student: $student,
                amount: $jumlahBayar,
                metode: $request->metode
            );
        });

        return back()->with('success', 'Pembayaran PMB berhasil disimpan!');
    }

    public function getData(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $tahun = $request->input('tahun');  // ex: 2025
        $bulan = $request->input('bulan');  // 1–12 atau null
        $kelasId = $request->input('kelas');  // id edu_class atau null

        // Query dasar: murid + kelas + sum pembayaran (filtered by tahun/bulan)
        $query = Student::query()
            ->select('id', 'name', 'edu_class_id')
            ->with('eduClass')
            ->withSum([
                'payments as total_bayar' => function ($q) use ($tahun, $bulan) {
                    if ($tahun) {
                        $q->whereYear('tanggal', $tahun);
                    }
                    if ($bulan) {
                        $q->whereMonth('tanggal', $bulan);
                    }
                }
            ], 'jumlah');

        // Filter kelas kalau dipilih
        if (!empty($kelasId)) {
            $query->where('edu_class_id', $kelasId);
        }

        return DataTables::of($query)
            // total_bayar sudah dihitung via withSum
            ->addColumn('kelas', function ($row) {
                return $row->eduClass->name ?? '-';
            })
            ->addColumn('total_bayar', function ($row) {
                return (int) ($row->total_bayar ?? 0);
            })
            ->addColumn('actions', function ($row) {
                $url = route('payment.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-sm btn-info">Lihat Detail</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function chartBulanan(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);
        $bulan = $request->input('bulan'); // optional
        $kelasId = $request->input('kelas');

        // Query total pembayaran per bulan
        $query = EduPayment::selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->whereYear('tanggal', $tahun);

        if ($bulan) {
            $query->whereMonth('tanggal', $bulan);
        }

        if ($kelasId) {
            $query->whereHas('student', function ($q) use ($kelasId) {
                $q->where('edu_class_id', $kelasId);
            });
        }

        $rows = $query->groupBy('bulan')->orderBy('bulan')->get();

        $labels = [];
        $values = [];

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create()->month($i)->translatedFormat('M');
            $item = $rows->firstWhere('bulan', $i);
            $values[] = $item ? (int) $item->total : 0;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    public function history($student_id)
    {
        $student = Student::with(['biaya', 'pembayaran'])->findOrFail($student_id);

        $totalBiaya = $student->biaya->sum('jumlah');
        $totalBayar = $student->pembayaran->sum('jumlah');
        $sisa = $totalBiaya - $totalBayar;

        return view('payment.history', compact('student', 'totalBiaya', 'totalBayar', 'sisa'));
    }

    public function exportKwitansi(Student $student)
    {
        $totalBiaya = DB::table('student_costs')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        $totalBayar = DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        $sisa = $totalBiaya - $totalBayar;

        $payments = DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        $pdf = pdf::loadView('pdf.kwitansi', compact('student', 'totalBiaya', 'totalBayar', 'sisa', 'payments'));

        return $pdf->download('kwitansi_' . $student->name . '.pdf');
    }

    public function verifikasiKwitansi($token)
    {
        $payment = EduPayment::with('student')->where('verifikasi_token', $token)->firstOrFail();

        return view('bidang.pendidikan.payments.verifikasi', compact('payment'));
    }

    public function cetakKwitansiPerTransaksi($payment_id)
    {
        $payment = EduPayment::with('student')->findOrFail($payment_id);
        $tahunAjaran = $payment->student->eduClass->tahun_ajaran;
        $nomorInduk = $payment->student->no_induk;
        $logo = public_path('img/photos/logo_yys.png');

        $pembayaranKe = EduPayment::where('student_id', $payment->student_id)
            ->where('id', '<=', $payment->id)
            ->count();

        $urlVerifikasi = route('payments.verifikasi', $payment->verifikasi_token);

        // Lokasi file QR sementara
        $qrFileName = 'qr_' . $payment->id . '.svg';
        $qrFilePath = storage_path('app/public/qrcodes/' . $qrFileName);

        // Pastikan foldernya ada
        if (!file_exists(dirname($qrFilePath))) {
            mkdir(dirname($qrFilePath), 0755, true);
        }

        // Simpan QR Code sebagai SVG
        file_put_contents($qrFilePath, QrCode::format('svg')->size(100)->generate($urlVerifikasi));

        $nomorKwitansi = 'PMB/' . $tahunAjaran . '/' . $nomorInduk . str_pad($pembayaranKe, 3, '0', STR_PAD_LEFT);

        // sisa tagihan
        $totalBiaya = DB::table('student_costs')->where('student_id', $payment->student_id)->sum('jumlah');
        $totalBayar = DB::table('edu_payments')->where('student_id', $payment->student_id)->sum('jumlah');
        $sisa = $totalBiaya - $totalBayar;
        $keterangan = $sisa <= 0 ? 'Lunas' : 'Cicilan PMB, sisa pembayaran Rp ' . number_format($sisa, 0, ',', '.');

        $tahunAjaranBersih = str_replace(['/', '\\'], '-', $tahunAjaran);
        $namaSiswaBersih = preg_replace('/[^A-Za-z0-9\-]/', '_', $payment->student->name);

        $namaFile = 'PMB-' . $tahunAjaranBersih . '-' . $nomorInduk . str_pad($pembayaranKe, 3, '0', STR_PAD_LEFT) . '-' . $namaSiswaBersih . '.pdf';

        return PDF::loadView('bidang.pendidikan.payments.kwitansi-per-pembayaran', [
            'payment' => $payment,
            'nomorKwitansi' => $nomorKwitansi,
            'keterangan' => $keterangan,
            'urlVerifikasi' => $urlVerifikasi,
            'qrPath' => $qrFilePath,
            'logo' => $logo,
        ])
            ->setPaper([0, 0, 227, 600]) // ➜ 80mm x ±210mm tinggi
            // ->setPaper([0, 0, 164, 500]) // Lebar 58mm, tinggi 176mm
            ->stream($namaFile);
    }

    // public function recognizePmbPreview(Student $student)
    // {
    //     return response()->json(
    //         RevenueRecognitionService::previewPMBRecognitionManual($student)
    //     );
    // }

    public function recognizePmbPreview(Student $student)
    {
        $payload = RevenueRecognitionService::previewPMBRecognitionManual($student);
        Log::info('PMB Preview', $payload);
        return response()->json($payload);
    }

    public function recognizePMB(Request $request, Student $student)
    {
        $request->validate([
            'akun_keuangan_id' => ['required', 'integer'],
        ]);

        $selectedAkunId = (int) $request->akun_keuangan_id;

        $amountToRecognize = RevenueRecognitionService::getRecognizableAmountPMB($student);

        if ($amountToRecognize <= 0) {
            return back()->with('error', "Tidak ada nominal PMB yang dapat diakui untuk {$student->name}.");
        }

        $result = RevenueRecognitionService::recognizePMBManualBySelectedCoa($student, $amountToRecognize, $selectedAkunId);

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
