<?php

use FontLib\Table\Type\name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/student-by-rfid/{uid}', function ($uid) {
    $student = App\Models\Student::where('rfid_uid', $uid)->first();

    if (!$student) {
        return response()->json(null);
    }

    $sudahBayar = $student->payments()->sum('jumlah');
    $sisa = $student->total_biaya - $sudahBayar;

    return response()->json([
        'id' => $student->id,
        'name' => $student->name,
        'edu_class' => $student->edu_class ? $student->edu_class->name : null,
        'tahun_ajaran' => $student->edu_class ? $student->edu_class->tahun_ajaran : null,
        'total_biaya' => $student->total_biaya,
        'sisa' => $sisa
    ]);
});

Route::get('/spp-tagihan-by-rfid/{uid}', function ($uid) {
    $student = App\Models\Student::where('rfid_uid', $uid)->first();

    if (!$student) {
        return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
    }

    $tagihans = $student->tagihanSpps()->get();

    return response()->json([
        'student_id' => $student->id,
        'student_name' => $student->name,
        'tagihan_count' => $tagihans->count(),
        'tagihan' => $tagihans
    ]);
});

