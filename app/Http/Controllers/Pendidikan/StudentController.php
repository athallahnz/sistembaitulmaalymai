<?php

namespace App\Http\Controllers\Pendidikan;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\WaliMurid;
use App\Models\StudentCost;
use App\Models\EduClass;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make($request->all(), [
            // Data murid
            'name' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'ttl' => 'nullable|date',
            'usia' => 'nullable|string|max:100',
            'nik' => 'nullable|string',
            'no_akte' => 'nullable|string',
            'no_kk' => 'nullable|string',
            'alamat_kk' => 'nullable|string',
            'alamat_tinggal' => 'nullable|string',
            'pas_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'akte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'edu_class_id' => 'required|exists:edu_classes,id',
            'rfid_uid' => 'required|unique:students,rfid_uid',
            'total_biaya' => 'required|numeric',

            // Data wali murid
            'wali_nama' => 'required|string',
            'wali_jenis_kelamin' => 'required|in:L,P',
            'wali_hubungan' => 'required|in:Ayah,Ibu,Wali',
            'wali_nik' => 'nullable|string',
            'wali_no_hp' => 'nullable|string',
            'wali_alamat' => 'nullable|string',
            'wali_foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Rincian biaya
            'akun_keuangan_id' => 'required|array|min:1',
            'akun_keuangan_id.*' => 'required|distinct|exists:akun_keuangans,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validasi gagal saat menyimpan siswa', [
                'errors' => $validator->errors()->all(),
            ]);

            return redirect()->back()->withErrors($validator)->withInput();
        }


        if (count($request->akun_keuangan_id) !== count($request->jumlah)) {
            return redirect()->back()->with('error', 'Data akun keuangan dan jumlah tidak seimbang.')->withInput();
        }

        // dd($request->all());

        DB::beginTransaction();

        try {
            // Upload file wali murid
            $fotoKtp = $request->file('wali_foto_ktp')
                ? $request->file('wali_foto_ktp')->store('wali_ktp')
                : null;

            // Simpan wali murid
            $wali = WaliMurid::create([
                'nama' => $request->wali_nama,
                'jenis_kelamin' => $request->wali_jenis_kelamin,
                'hubungan' => $request->wali_hubungan,
                'nik' => $request->wali_nik,
                'no_hp' => $request->wali_no_hp,
                'alamat' => $request->wali_alamat,
                'foto_ktp' => $fotoKtp,
            ]);

            $ttl = null;
            if ($request->ttl) {
                $ttl = \Carbon\Carbon::createFromFormat('d/m/Y', $request->ttl)->format('Y-m-d');
            }

            // Upload dokumen murid
            $pasPhoto = $request->file('pas_photo')
                ? $request->file('pas_photo')->store('students/photo')
                : null;
            $akte = $request->file('akte')
                ? $request->file('akte')->store('students/akte')
                : null;
            $kk = $request->file('kk')
                ? $request->file('kk')->store('students/kk')
                : null;

            // Simpan siswa
            $student = Student::create([
                'name' => $request->name,
                'jenis_kelamin' => $request->jenis_kelamin,
                'ttl' => $ttl,
                'usia' => $request->usia,
                'nik' => $request->nik,
                'no_akte' => $request->no_akte,
                'no_kk' => $request->no_kk,
                'alamat_kk' => $request->alamat_kk,
                'alamat_tinggal' => $request->alamat_tinggal,
                'edu_class_id' => $request->edu_class_id,
                'rfid_uid' => $request->rfid_uid,
                'total_biaya' => $request->total_biaya,
                'wali_murid_id' => $wali->id,
                'pas_photo' => $pasPhoto,
                'akte' => $akte,
                'kk' => $kk,
            ]);

            // Simpan rincian biaya
            $pairs = array_combine($request->akun_keuangan_id, $request->jumlah);

            foreach ($pairs as $akunId => $jumlah) {
                StudentCost::create([
                    'student_id' => $student->id,
                    'akun_keuangan_id' => $akunId,
                    'jumlah' => $jumlah,
                ]);
            }

            DB::commit();
            return redirect()->route('students.index')->with('success', 'Data murid dan rincian biaya berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Tambahkan log error detail
            \Log::error('Gagal menyimpan data siswa: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
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
