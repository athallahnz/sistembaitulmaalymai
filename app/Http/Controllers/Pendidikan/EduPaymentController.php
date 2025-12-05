<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\EduPayment;
use App\Models\Piutang;
use App\Models\Student;
use App\Models\Transaksi;
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
        // Ambil total biaya dari student_cost
        $totalBiaya = DB::table('student_costs')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        // Ambil total pembayaran dari edu_payment
        $totalBayar = DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->sum('jumlah');

        // Sisa tanggungan
        $sisa = $totalBiaya - $totalBayar;

        // Ambil data pembayaran
        $payments = DB::table('edu_payments')
            ->where('student_id', $student->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('bidang.pendidikan.payments.detail', [
            'student' => $student,
            'totalBiaya' => $totalBiaya,
            'totalBayar' => $totalBayar,
            'sisa' => $sisa,
            'payments' => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'jumlah' => 'required|numeric|min:1000',
            'metode' => 'required|in:tunai,transfer'
        ]);

        // Ambil data student beserta relasi biaya dan pembayaran
        $student = Student::with(['biaya', 'payments'])->findOrFail($request->student_id);

        // Hitung total biaya dan total bayar
        $totalBiaya = $student->biaya->sum('jumlah');
        $totalBayar = $student->payments->sum('jumlah');
        $sisa = $totalBiaya - $totalBayar;

        // Jika sudah lunas atau tidak ada sisa, tolak pembayaran
        if ($sisa <= 0) {
            return back()->with(['error' => 'Pembayaran ditolak: Siswa sudah lunas.']);
        }

        // Jika jumlah pembayaran melebihi sisa, juga bisa batasi (opsional)
        if ($request->jumlah > $sisa) {
            return back()->with(['error' => "Pembayaran melebihi sisa tagihan Rp " . number_format($sisa, 0, ',', '.')]);
        }

        // Simpan pembayaran
        EduPayment::create([
            'student_id' => $request->student_id,
            'jumlah' => $request->jumlah,
            'tanggal' => now(),
            'verifikasi_token' => Str::random(20),
        ]);

        // Trigger jurnal double-entry
        StudentPaymentService::recordPayment($student, $request->jumlah, $request->metode);

        return back()->with('success', 'Pembayaran berhasil disimpan!');
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
    public function recognizePMB(Student $student)
    {
        $akunPiutangPMB = config('akun.piutang_pmb'); // 1032

        // Ambil piutang utama PMB untuk siswa ini
        $piutang = Piutang::where('student_id', $student->id)
            ->where('akun_keuangan_id', $akunPiutangPMB)
            ->orderBy('id', 'asc')
            ->first();

        // 1) Kalau piutang TIDAK ADA → anggap tagihan PMB belum pernah dibuat → BLOK
        if (!$piutang) {
            return back()->with(
                'error',
                "Pengakuan pendapatan PMB untuk {$student->name} tidak dapat dilakukan karena tagihan PMB belum terdaftar (piutang tidak ditemukan)."
            );
        }

        // 2) Kalau piutang MASIH ADA SISA (> 0) → BELUM LUNAS → BLOK
        if ($piutang->jumlah > 0 && $piutang->status === 'belum_lunas') {
            return back()->with(
                'error',
                "Pengakuan pendapatan PMB untuk {$student->name} belum dapat dilakukan karena tagihan PMB masih BELUM LUNAS (sisa: " .
                number_format($piutang->jumlah, 0, ',', '.') . ")."
            );
        }

        // 3) Opsional: kalau status belum diset 'lunas' tapi jumlah sudah 0, kita bisa rapikan:
        if ($piutang->jumlah <= 0 && $piutang->status !== 'lunas') {
            $piutang->update([
                'jumlah' => 0,
                'status' => 'lunas',
                'deskripsi' => 'Auto-set lunas sebelum pengakuan pendapatan PMB',
            ]);
        }

        // 4) Cek apakah sudah pernah dilakukan pengakuan pendapatan PMB
        $sudahRecognized = Transaksi::where('type', 'pengakuan_pendapatan')
            ->where('sumber', $student->id)
            ->exists();

        if ($sudahRecognized) {
            return back()->with(
                'error',
                "Pengakuan pendapatan PMB untuk {$student->name} sudah pernah dilakukan sebelumnya."
            );
        }

        // 5) Baru jalankan pengakuan pendapatan
        RevenueRecognitionService::recognizePMB($student);

        return back()->with(
            'success',
            "Pengakuan pendapatan PMB untuk {$student->name} berhasil diproses."
        );
    }

}
