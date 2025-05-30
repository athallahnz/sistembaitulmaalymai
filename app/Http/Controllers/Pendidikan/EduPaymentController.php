<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\EduPayment;
use Illuminate\Http\Request;

class EduPaymentController extends Controller
{
    public function store(Request $request) {
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'jumlah' => 'required|numeric|min:1000'
    ]);

    EduPayment::create([
        'student_id' => $request->student_id,
        'jumlah' => $request->jumlah,
        'tanggal' => now()
    ]);

    return back()->with('success', 'Pembayaran berhasil disimpan!');
}
}
