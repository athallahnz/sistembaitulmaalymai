<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentCost;
use App\Models\EduClass;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\ValidationException;




class StudentController extends Controller
{
    // Tampilkan daftar student (dashboard)
    public function index()
    {
        $students = Student::orderBy('name')->paginate(10);
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        $akunKeuangans = AkunKeuangan::where('parent_id', 202)->get();
        return view('bidang.pendidikan.student_index', compact('students', 'eduClasses', 'akunKeuangans'));
    }

    public function getAkunKeuanganByClass($id)
    {
        // Misalnya relasi many-to-many:
        $class = EduClass::findOrFail($id);
        $akunKeuangans = $class->akunKeuangans; // asumsi relasi belongsToMany

        return response()->json($akunKeuangans);
    }


    // Form tambah student
    public function create()
    {
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        return view('bidang.pendidikan.student_create');
    }

    // Simpan student baru
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'edu_class_id' => 'required|integer|exists:edu_classes,id',
                'total_biaya' => 'required|numeric|min:10000',
                'rfid_uid' => 'required|string|unique:students,rfid_uid',
                'akun_keuangan_id.*' => 'required|exists:akun_keuangans,id',
                'jumlah.*' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            // Simpan siswa
            $student = Student::create($request->only(['name', 'edu_class_id', 'total_biaya', 'rfid_uid']));


            // Simpan rincian biaya
            if (!$request->has('akun_keuangan_id') || !$request->has('jumlah')) {
                throw new \Exception('Data akun keuangan atau jumlah tidak ditemukan.');
            }

            if (count($request->akun_keuangan_id) !== count($request->jumlah)) {
                throw new \Exception('Data akun keuangan dan jumlah tidak seimbang');
            }

            $pairs = array_combine($request->akun_keuangan_id, $request->jumlah);



            foreach ($pairs as $akunId => $jumlah) {
                StudentCost::create([
                    'student_id' => $student->id,
                    'akun_keuangan_id' => $akunId,
                    'jumlah' => $jumlah,
                ]);
            }

            DB::commit();

            return redirect()->route('students.index')->with('success', 'Murid berhasil didaftarkan beserta rincian biayanya.');
        } catch (ValidationException $e) {
            Log::error('Validasi gagal saat mendaftarkan siswa:', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return redirect()->back()
            ->withErrors($e->validator)
            ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Valid akun_keuangan_id', $request->akun_keuangan_id);
            Log::info('Valid jumlah', $request->jumlah);
            Log::error('Gagal menyimpan data siswa:', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function getData(Request $request)
    {
        $data = Student::query();

        return DataTables::of($data)
            ->addColumn('actions', function ($row) {
                $editUrl = route('students.edit', $row->id);
                $deleteUrl = route('students.destroy', $row->id);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                return <<<HTML
                <a href="{$editUrl}" class="btn btn-warning btn-sm me-2 mb-2">
                    <i class="bi bi-pencil-square"></i></a>
                <form action="{$deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                    {$csrf}
                    {$method}
                    <button class="btn btn-danger btn-sm mb-2"><i class="bi bi-trash"></i></button>
                </form>
            HTML;
            })
            ->addColumn('kelas', function ($student) {
                return $student->eduClass->name . ' - ' . $student->eduClass->tahun_ajaran;
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    // Form edit student
    public function edit(Student $student)
    {
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        return view('bidang.pendidikan.student_edit', compact('student', 'eduClasses'));

    }

    // Update data student
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'edu_class_id' => 'required|string|max:50',
            'total_biaya' => 'required|numeric|min:10000',
            // Kalau rfid_uid diganti, cek unique kecuali id current
            'rfid_uid' => 'required|string|unique:students,rfid_uid,' . $student->id,
        ]);

        $student->update($request->only(['name', 'edu_class_id', 'total_biaya', 'rfid_uid']));

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    // Hapus student
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
