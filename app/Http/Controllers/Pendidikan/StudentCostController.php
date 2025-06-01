<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AkunKeuangan;
use App\Models\StudentCost;

class StudentCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Student $student)
    {
        $akunKeuangans = AkunKeuangan::all();
        return view('student_costs.create', compact('student', 'akunKeuangans'));
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'akun_keuangan_id.*' => 'required|exists:akun_keuangans,id',
            'jumlah.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->akun_keuangan_id as $index => $akunId) {
            StudentCost::create([
                'student_id' => $student->id,
                'akun_keuangan_id' => $akunId,
                'jumlah' => $request->jumlah[$index],
            ]);
        }

        return redirect()->route('students.show', $student)->with('success', 'Biaya berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
