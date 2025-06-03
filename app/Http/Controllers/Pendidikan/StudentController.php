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
        $messages = [
            // Data murid
            'name.required' => 'Nama murid wajib diisi.',
            'name.string' => 'Nama murid harus berupa teks.',

            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',

            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tempat_lahir.string' => 'Tempat lahir harus berupa teks.',

            'usia.string' => 'Usia harus berupa teks.',
            'usia.max' => 'Usia maksimal 100 karakter.',

            'nik.string' => 'NIK harus berupa teks.',
            'no_akte.string' => 'No. Akte harus berupa teks.',
            'no_kk.string' => 'No. KK harus berupa teks.',
            'alamat_kk.string' => 'Alamat KK harus berupa teks.',
            'alamat_tinggal.string' => 'Alamat tinggal harus berupa teks.',

            'pas_photo.image' => 'Pas foto harus berupa file gambar.',
            'pas_photo.mimes' => 'Pas foto harus berformat jpeg, png, atau jpg.',
            'pas_photo.max' => 'Pas foto maksimal berukuran 2MB.',

            'akte.file' => 'File akte harus berupa file.',
            'akte.mimes' => 'File akte harus berformat pdf, jpg, jpeg, atau png.',
            'akte.max' => 'File akte maksimal 2MB.',

            'kk.file' => 'File KK harus berupa file.',
            'kk.mimes' => 'File KK harus berformat pdf, jpg, jpeg, atau png.',
            'kk.max' => 'File KK maksimal 2MB.',

            'edu_class_id.required' => 'Kelas pendidikan wajib dipilih.',
            'edu_class_id.exists' => 'Kelas pendidikan tidak valid.',

            'rfid_uid.required' => 'RFID UID wajib diisi.',
            'rfid_uid.unique' => 'RFID UID sudah digunakan.',

            'total_biaya.required' => 'Total biaya wajib diisi.',
            'total_biaya.numeric' => 'Total biaya harus berupa angka.',

            // Data wali murid
            'wali_nama.required' => 'Nama wali murid wajib diisi.',
            'wali_nama.string' => 'Nama wali murid harus berupa teks.',

            'wali_jenis_kelamin.required' => 'Jenis kelamin wali murid wajib dipilih.',
            'wali_jenis_kelamin.in' => 'Jenis kelamin wali harus L atau P.',

            'wali_hubungan.required' => 'Hubungan dengan murid wajib dipilih.',
            'wali_hubungan.in' => 'Hubungan wali harus Ayah, Ibu, atau Wali.',

            'wali_nik.string' => 'NIK wali harus berupa teks.',
            'wali_no_hp.required' => 'No. Handphone wali wajib diisi.',
            'wali_no_hp.string' => 'No. Handphone wali harus berupa teks.',

            'wali_email.required' => 'Email wali wajib diisi.',
            'wali_email.email' => 'Email wali harus berupa alamat email yang valid.',
            'wali_email.unique' => 'Email wali sudah digunakan.',

            'wali_pendidikan_terakhir.string' => 'Pendidikan terakhir wali harus berupa teks.',
            'wali_pekerjaan.string' => 'Pekerjaan wali harus berupa teks.',
            'wali_alamat.string' => 'Alamat wali harus berupa teks.',

            'wali_foto_ktp.image' => 'Foto KTP wali harus berupa file gambar.',
            'wali_foto_ktp.mimes' => 'Foto KTP wali harus berformat jpeg, png, atau jpg.',
            'wali_foto_ktp.max' => 'Foto KTP wali maksimal 2MB.',

            // Rincian biaya
            'akun_keuangan_id.required' => 'Setidaknya satu akun keuangan wajib dipilih.',
            'akun_keuangan_id.array' => 'Akun keuangan harus berupa array.',
            'akun_keuangan_id.min' => 'Minimal satu akun keuangan harus dipilih.',
            'akun_keuangan_id.*.required' => 'Akun keuangan tidak boleh kosong.',
            'akun_keuangan_id.*.distinct' => 'Terdapat akun keuangan yang duplikat.',
            'akun_keuangan_id.*.exists' => 'Akun keuangan tidak valid.',

            'jumlah.required' => 'Jumlah nominal wajib diisi.',
            'jumlah.array' => 'Jumlah nominal harus berupa array.',
            'jumlah.min' => 'Minimal satu jumlah nominal harus diisi.',
            'jumlah.*.required' => 'Jumlah nominal tidak boleh kosong.',
            'jumlah.*.numeric' => 'Jumlah nominal harus berupa angka.',
            'jumlah.*.min' => 'Jumlah nominal tidak boleh negatif.',
        ];

        $validator = Validator::make($request->all(), [
            // Data murid
            'name' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'required|string',
            'ttl' => 'nullable|date',
            'usia' => 'required|string|max:100',
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
            'wali_no_hp' => 'required|string',
            'wali_email' => 'required|email|unique:wali_murids,email',
            'wali_pendidikan_terakhir' => 'nullable|string',
            'wali_pekerjaan' => 'nullable|string',
            'wali_alamat' => 'nullable|string',
            'wali_foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Rincian biaya
            'akun_keuangan_id' => 'required|array|min:1',
            'akun_keuangan_id.*' => 'required|distinct|exists:akun_keuangans,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|numeric|min:0',
        ], $messages);

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
                ? $request->file('pas_photo')->store('public/students/photo')
                : null;
            $akte = $request->file('akte')
                ? $request->file('akte')->store('public/students/akte')
                : null;
            $kk = $request->file('kk')
                ? $request->file('kk')->store('public/students/kk')
                : null;

            // Simpan siswa
            $student = Student::create([
                'name' => $request->name,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
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
                $showUrl = route('students.show', $row->id);
                $editUrl = route('students.edit', $row->id);
                $deleteUrl = route('students.destroy', $row->id);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                return <<<HTML
                <a href="{$showUrl}" class="btn btn-info btn-sm me-2 mb-2">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{$editUrl}" class="btn btn-warning btn-sm me-2 mb-2">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <form action="{$deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                    {$csrf}
                    {$method}
                    <button class="btn btn-danger btn-sm mb-2">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            HTML;
            })

            ->addColumn('kelas', function ($student) {
                return $student->eduClass->name . ' - ' . $student->eduClass->tahun_ajaran;
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function show($id)
    {
        $student = Student::findOrFail($id);
        $students = Student::with('waliMurid')->find($id);
        return view('bidang.pendidikan.student_show', compact('student','students'));
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
