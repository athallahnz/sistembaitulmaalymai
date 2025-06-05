<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\EduPayment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

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

        return view('bidang.pendidikan.payments.dashboard', compact('data', 'labels', 'values'));
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
            'jumlah' => 'required|numeric|min:1000'
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
            'tanggal' => now()
        ]);

        return back()->with('success', 'Pembayaran berhasil disimpan!');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Ambil data murid dan total bayar (anggap relasi ke `payments`)
            $data = Student::withSum('payments', 'jumlah') // asumsi kolom jumlah
                ->select('id', 'name');

            return DataTables::of($data)
                ->addColumn('total_bayar', function ($row) {
                    return $row->payments->sum('jumlah');
                })
                ->addColumn('actions', function ($row) {
                    $url = route('payment.show', $row->id);
                    return '<a href="' . $url . '" class="btn btn-sm btn-info">Lihat Detail</a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
    }

    public function history($student_id)
    {
        $student = Student::with(['biaya', 'pembayaran'])->findOrFail($student_id);

        $totalBiaya = $student->biaya->sum('jumlah');
        $totalBayar = $student->pembayaran->sum('jumlah');
        $sisa = $totalBiaya - $totalBayar;

        return view('payment.history', compact('student', 'totalBiaya', 'totalBayar', 'sisa'));
    }
}
