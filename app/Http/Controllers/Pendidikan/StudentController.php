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
            'nisn.required' => 'No. Induk wajib diisi.',
            'nisn.max' => 'NISN tidak boleh lebih dari 9 karakter.',
            'no_induk.required' => 'No. Induk wajib diisi.',
            'name.required' => 'Nama murid wajib diisi.',
            'nickname.required' => 'Nama Panggilan wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            'ttl.date' => 'Tanggal lahir tidak valid.',
            'usia.string' => 'Usia harus berupa teks.',
            'nik.max' => 'NISN tidak boleh lebih dari 16 karakter.',
            'no_kk.max' => 'NISN tidak boleh lebih dari 16 karakter.',
            'rfid_uid.required' => 'RFID UID wajib diisi.',
            'rfid_uid.unique' => 'RFID UID sudah digunakan.',
            'total_biaya.required' => 'Total biaya wajib diisi.',
            'total_biaya.numeric' => 'Total biaya harus berupa angka.',
            'edu_class_id.required' => 'Kelas pendidikan wajib dipilih.',
            'edu_class_id.exists' => 'Kelas pendidikan tidak valid.',

            // Wali
            'wali.nama.*' => 'Nama wali murid wajib diisi.',
            'wali.jenis_kelamin.*' => 'Jenis kelamin wali murid wajib dipilih.',
            'wali.nik.*.max' => 'NISN tidak boleh lebih dari 16 karakter.',
            'wali.hubungan.*' => 'Hubungan wali harus Ayah atau Ibu.',
            'wali.no_hp.*' => 'No HP wali wajib diisi.',
            'wali.email.*' => 'Email wali wajib diisi dan valid.',
            'wali.foto_ktp.*.image' => 'Foto KTP harus berupa gambar.',
            'wali.foto_ktp.*.mimes' => 'Format KTP harus jpeg, png, atau jpg.',
            'wali.foto_ktp.*.max' => 'Ukuran KTP maksimal 2MB.',

            // Biaya
            'akun_keuangan_id.*.required' => 'Akun keuangan tidak boleh kosong.',
            'akun_keuangan_id.*.exists' => 'Akun keuangan tidak valid.',
            'jumlah.*.required' => 'Jumlah nominal wajib diisi.',
            'jumlah.*.numeric' => 'Jumlah nominal harus angka.',
        ];

        // Konversi TTL
        if ($request->filled('ttl')) {
            try {
                $request->merge([
                    'ttl' => \Carbon\Carbon::createFromFormat('d/m/Y', $request->ttl)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                return back()->withErrors(['ttl' => 'Format tanggal lahir tidak valid.'])->withInput();
            }
        }

        // ✅ Validasi
        $validator = Validator::make($request->all(), [
            'no_induk' => 'required|string|unique:students,no_induk',
            'nisn' => 'required|string|max:9',
            'name' => 'required|string',
            'nickname' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'nullable|string',
            'ttl' => 'nullable|date',
            'usia' => 'nullable|string|max:100',
            'nik' => 'nullable|string|max:16',
            'no_akte' => 'nullable|string',
            'no_kk' => 'nullable|string|max:16',
            'alamat_kk' => 'nullable|string',
            'alamat_tinggal' => 'nullable|string',
            'pas_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'akte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'rfid_uid' => 'required|unique:students,rfid_uid',
            'edu_class_id' => 'required|exists:edu_classes,id',
            'total_biaya' => 'required|numeric',

            // Wali
            'wali.nama.*' => 'required|string',
            'wali.hubungan.*' => 'required|in:Ayah,Ibu',
            'wali.jenis_kelamin.*' => 'required|in:L,P',
            'wali.nik.*' => 'nullable|string|max:16',
            'wali.no_hp.*' => 'required|string',
            'wali.email.*' => 'required|email|distinct',
            'wali.pendidikan_terakhir.*' => 'nullable|string',
            'wali.pekerjaan.*' => 'nullable|string',
            'wali.alamat.*' => 'nullable|string',
            'wali.foto_ktp.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Biaya
            'akun_keuangan_id' => 'required|array|min:1',
            'akun_keuangan_id.*' => 'required|distinct|exists:akun_keuangans,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|numeric|min:0',
        ], $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (count($request->akun_keuangan_id) !== count($request->jumlah)) {
            return back()->with('error', 'Jumlah akun dan nominal tidak seimbang.')->withInput();
        }

        DB::beginTransaction();

        try {
            // Upload dokumen siswa
            $pasPhoto = $request->file('pas_photo')?->store('students/photo', 'public');
            $akte = $request->file('akte')?->store('students/akte', 'public');
            $kk = $request->file('kk')?->store('students/kk', 'public');

            $student = Student::create([
                'no_induk' => $request->no_induk,
                'nisn' => $request->nisn,
                'name' => $request->name,
                'nickname' => $request->nickname,
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

            // Simpan dua wali murid
            foreach ($request->wali['nama'] as $i => $nama) {
                $fotoKtp = null;
                if ($request->hasFile("wali.foto_ktp.$i")) {
                    $fotoKtp = $request->file("wali.foto_ktp.$i")->store('wali_ktp', 'public');
                }

                $wali = WaliMurid::create([
                    'student_id' => $student->id,
                    'nama' => $nama,
                    'hubungan' => $request->wali['hubungan'][$i],
                    'jenis_kelamin' => $request->wali['jenis_kelamin'][$i],
                    'nik' => $request->wali['nik'][$i] ?? null,
                    'no_hp' => $request->wali['no_hp'][$i],
                    'email' => $request->wali['email'][$i],
                    'pendidikan_terakhir' => $request->wali['pendidikan_terakhir'][$i] ?? null,
                    'pekerjaan' => $request->wali['pekerjaan'][$i] ?? null,
                    'alamat' => $request->wali['alamat'][$i] ?? null,
                    'foto_ktp' => $fotoKtp,
                ]);
            }

            // Pair akun dan nominal biaya
            $biayaPairs = array_combine($request->akun_keuangan_id, $request->jumlah);

            // Simpan ke student_cost
            foreach ($biayaPairs as $akunId => $nominal) {
                StudentCost::create([
                    'student_id' => $student->id,
                    'akun_keuangan_id' => $akunId,
                    'jumlah' => $nominal,
                ]);
            }

            // ✅ Trigger keuangan otomatis (refaktor di Service)
            app(\App\Services\StudentFinanceService::class)->handleNewStudentFinance($student, $biayaPairs);

            DB::commit();
            return redirect()->route('students.index')->with('success', 'Data murid dan wali beserta rincian biaya berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal simpan siswa: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data.')->withInput();
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
        $student = Student::with('eduClass', 'waliMurids')->findOrFail($id);

        // Ambil wali berdasarkan hubungan
        $ayah = optional($student->wali_murids)->firstWhere('hubungan', 'Ayah');
        $ibu = optional($student->wali_murids)->firstWhere('hubungan', 'Ibu');


        return view('bidang.pendidikan.student_show', compact('student', 'ayah', 'ibu'));
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
