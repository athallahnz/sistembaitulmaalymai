<?php

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

Route::get('/student-by-rfid/{uid}', function($uid) {
    $student = App\Models\Student::where('rfid_uid', $uid)->first();

    if (!$student) {
        return response()->json(null);
    }

    $sudahBayar = $student->payments()->sum('jumlah');
    $sisa = $student->total_biaya - $sudahBayar;

    return response()->json([
        'id' => $student->id,
        'name' => $student->name,
        'kelas' => $student->kelas,
        'total_biaya' => $student->total_biaya,
        'sisa' => $sisa
    ]);
});
