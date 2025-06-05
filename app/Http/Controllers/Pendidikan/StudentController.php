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
        $students = Student::orderBy('name');
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
            'no_induk.required' => 'No. Induk wajib diisi.',
            'no_induk.string' => 'No. Induk harus berupa teks.',

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

        if ($request->filled('ttl')) {
            try {
                // Konversi dari d/m/Y ke Y-m-d agar lolos validasi 'date'
                $request->merge([
                    'ttl' => \Carbon\Carbon::createFromFormat('d/m/Y', $request->ttl)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Format TTL tidak valid: ' . $request->ttl);
                return redirect()->back()
                    ->withErrors(['ttl' => 'Format tanggal lahir tidak valid. Gunakan format dd/mm/yyyy.'])
                    ->withInput();
            }
        }

        $validator = Validator::make($request->all(), [
            // Data murid
            'no_induk' => 'required|string|unique:students,no_induk',
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
                ? $request->file('wali_foto_ktp')->store('wali_ktp', 'public')
                : null;



            // Upload dokumen murid
            $pasPhoto = $request->file('pas_photo')
                ? $request->file('pas_photo')->store('students/photo', 'public')
                : null;
            $akte = $request->file('akte')
                ? $request->file('akte')->store('students/akte', 'public')
                : null;
            $kk = $request->file('kk')
                ? $request->file('kk')->store('students/kk', 'public')
                : null;

            // 1. Simpan siswa terlebih dahulu
            $student = Student::create([
                'no_induk' => $request->no_induk,
                'name' => $request->name,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'ttl' => $request->ttl,
                'usia' => $request->usia,
                'nik' => $request->nik,
                'no_akte' => $request->no_akte,
                'no_kk' => $request->no_kk,
                'alamat_kk' => $request->alamat_kk,
                'alamat_tinggal' => $request->alamat_tinggal,
                'edu_class_id' => $request->edu_class_id,
                'rfid_uid' => $request->rfid_uid,
                'total_biaya' => $request->total_biaya,
                'pas_photo' => $pasPhoto,
                'akte' => $akte,
                'kk' => $kk,
            ]);

            // 2. Simpan wali murid dan hubungkan dengan siswa yang baru dibuat
            $wali = WaliMurid::create([
                'nama' => $request->wali_nama,
                'jenis_kelamin' => $request->wali_jenis_kelamin,
                'hubungan' => $request->wali_hubungan,
                'nik' => $request->wali_nik,
                'no_hp' => $request->wali_no_hp,
                'email' => $request->wali_email,
                'pendidikan_terakhir' => $request->wali_pendidikan_terakhir,
                'pekerjaan' => $request->wali_pekerjaan,
                'alamat' => $request->wali_alamat,
                'foto_ktp' => $fotoKtp,
                'student_id' => $student->id,
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
        $student = Student::with('waliMurid')->findOrFail($id);
        return view('bidang.pendidikan.student_show', compact('student'));
    }

    // Form edit student
    public function edit(Student $student)
    {
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        $class = EduClass::findOrFail($student->edu_class_id); // sesuaikan relasi
        $akunKeuangans = $class->akunKeuangans;
        $studentCosts = DB::table('student_costs')
            ->join('akun_keuangans', 'student_costs.akun_keuangan_id', '=', 'akun_keuangans.id')
            ->where('student_costs.student_id', $student->id)
            ->select('akun_keuangans.nama_akun as nama_akun', 'student_costs.jumlah')
            ->get();
        return view('bidang.pendidikan.student_edit', compact('student', 'eduClasses', 'akunKeuangans', 'class', 'studentCosts'));
    }


    // Update data student
    public function update(Request $request, $id)
    {
        if ($request->filled('ttl')) {
            try {
                // Konversi dari d/m/Y ke Y-m-d agar lolos validasi 'date'
                $request->merge([
                    'ttl' => \Carbon\Carbon::createFromFormat('d/m/Y', $request->ttl)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Format TTL tidak valid: ' . $request->ttl);
                return redirect()->back()
                    ->withErrors(['ttl' => 'Format tanggal lahir tidak valid. Gunakan format dd/mm/yyyy.'])
                    ->withInput();
            }
        }
        $student = Student::findOrFail($id);

        // Validasi input
        $validated = $request->validate([
            'rfid_uid' => 'nullable|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'nullable|string|max:255',
            'ttl' => 'nullable|date',
            'usia' => 'required|string|max:100',
            'nik' => 'nullable|string|max:255',
            'no_akte' => 'nullable|string|max:255',
            'no_kk' => 'nullable|string|max:255',
            'alamat_kk' => 'nullable|string',
            'alamat_tinggal' => 'nullable|string',
            'pas_photo' => 'nullable|image|max:2048',
            'akte' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'wali_nama' => 'nullable|string|max:255',
            'wali_jenis_kelamin' => 'nullable|in:L,P',
            'wali_hubungan' => 'required|in:Ayah,Ibu,Wali',
            'wali_nik' => 'nullable|string',
            'wali_no_hp' => 'required|string',
            'wali_email' => 'required|email|unique:wali_murids,email,' . ($student->wali_murid_id ?? 'null') . ',id',
            'wali_pendidikan_terakhir' => 'nullable|string',
            'wali_pekerjaan' => 'nullable|string',
            'wali_alamat' => 'nullable|string',
            'wali_foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Update data student
        $student->update([
            'rfid_uid' => $validated['rfid_uid'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'tempat_lahir' => $validated['tempat_lahir'],
            'ttl' => $validated['ttl'],
            'usia' => $validated['usia'],
            'nik' => $validated['nik'],
            'no_akte' => $validated['no_akte'],
            'no_kk' => $validated['no_kk'],
            'alamat_kk' => $validated['alamat_kk'],
            'alamat_tinggal' => $validated['alamat_tinggal'],
        ]);

        // Upload dokumen
        if ($request->hasFile('pas_photo')) {
            if ($student->pas_photo) {
                Storage::delete($student->pas_photo);
            }
            $student->pas_photo = $request->file('pas_photo')->store('pas_photo');
        }

        if ($request->hasFile('akte')) {
            if ($student->akte) {
                Storage::delete($student->akte);
            }
            $student->akte = $request->file('akte')->store('dokumen_akte');
        }

        if ($request->hasFile('kk')) {
            if ($student->kk) {
                Storage::delete($student->kk);
            }
            $student->kk = $request->file('kk')->store('dokumen_kk');
        }

        // Update or create wali murid
        $waliMurid = WaliMurid::updateOrCreate(
            ['id' => $student->wali_murid_id],
            [
                'nama' => $validated['wali_nama'],
                'jenis_kelamin' => $validated['wali_jenis_kelamin'],
                'hubungan' => $validated['wali_hubungan'],
                'nik' => $validated['wali_nik'] ?? null,
                'no_hp' => $validated['wali_no_hp'],
                'email' => $validated['wali_email'],
                'pendidikan_terakhir' => $validated['wali_pendidikan_terakhir'] ?? null,
                'pekerjaan' => $validated['wali_pekerjaan'] ?? null,
                'alamat' => $validated['wali_alamat'] ?? null,
            ]
        );
        if ($request->hasFile('wali_foto_ktp')) {
            // Hapus file lama jika ada
            if ($waliMurid->foto_ktp && Storage::disk('public')->exists($waliMurid->foto_ktp)) {
                Storage::disk('public')->delete($waliMurid->foto_ktp);
            }

            // Simpan file baru
            $file = $request->file('wali_foto_ktp');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('wali_ktp', $filename, 'public');
            $waliMurid->foto_ktp = $path;
            $waliMurid->save();
        }


        // Update foreign key di tabel students
        $student->wali_murid_id = $waliMurid->id;
        $student->save();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui!');
    }

    // Hapus student
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
