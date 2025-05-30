<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class StudentController extends Controller
{
    // Tampilkan daftar student (dashboard)
    public function index()
    {
        $students = Student::orderBy('name')->paginate(10);
        return view('bidang.pendidikan.student_index', compact('students'));
    }

    // Form tambah student
    public function create()
    {
        return view('bidang.pendidikan.student_create');
    }

    // Simpan student baru
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'kelas' => 'required|string|max:50',
                'total_biaya' => 'required|numeric|min:10000',
                'rfid_uid' => 'required|string|unique:students,rfid_uid'
            ]);

            Student::create($request->only(['name', 'kelas', 'total_biaya', 'rfid_uid']));

            return redirect()->route('students.index')->with('success', 'Murid berhasil didaftarkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validasi gagal saat mendaftarkan siswa:', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan data siswa:', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }


    // Form edit student
    public function edit(Student $student)
    {
        return view('bidang.pendidikan.student_edit', compact('student'));
    }

    // Update data student
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'kelas' => 'required|string|max:50',
            'total_biaya' => 'required|numeric|min:10000',
            // Kalau rfid_uid diganti, cek unique kecuali id current
            'rfid_uid' => 'required|string|unique:students,rfid_uid,' . $student->id,
        ]);

        $student->update($request->only(['name', 'kelas', 'total_biaya', 'rfid_uid']));

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    // Hapus student
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
