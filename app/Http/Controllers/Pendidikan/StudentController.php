<?php

namespace App\Http\Controllers\Pendidikan;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\WaliMurid;
use App\Models\StudentCost;
use App\Models\EduClass;
use App\Models\AkunKeuangan;
use App\Services\StudentFinanceService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\ValidationException;




class StudentController extends Controller
{
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

    public function create()
    {
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        return view('bidang.pendidikan.student_create');
    }

    public function store(Request $request)
    {
        $messages = [
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
            'nisn' => 'nullable|string|max:9',
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
            'wali.email.*' => 'required|email',
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

                return <<<HTML
        <a href="{$showUrl}" class="btn btn-info btn-sm me-2 mb-2">
            <i class="bi bi-eye"></i>
        </a>
        <a href="{$editUrl}" class="btn btn-warning btn-sm me-2 mb-2">
            <i class="bi bi-pencil-square"></i>
        </a>
        <button type="button" class="btn btn-danger btn-sm mb-2 btn-hapus" data-url="{$deleteUrl}">
            <i class="bi bi-trash"></i>
        </button>
        <form id="delete-form" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
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
        // Ambil student beserta relasi kelas dan semua wali murid
        $student = Student::with(['eduClass', 'waliMurids'])->findOrFail($id);

        // Ambil wali berdasarkan hubungan
        $ayah = $student->waliMurids->firstWhere('hubungan', 'Ayah');
        $ibu = $student->waliMurids->firstWhere('hubungan', 'Ibu');

        return view('bidang.pendidikan.student_show', compact('student', 'ayah', 'ibu'));
    }

    public function edit(Student $student)
    {
        // Eager load: costs.akunKeuangan + waliMurids
        $student->load(['costs', 'costs.akunKeuangan', 'waliMurids']);

        // Ambil semua kelas yang tersedia untuk dropdown
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();

        // Ambil detail kelas murid untuk ambil akun keuangan yang terkait
        $class = EduClass::findOrFail($student->edu_class_id);

        // Ambil daftar akun keuangan berdasarkan kelas
        $akunKeuangans = $class->akunKeuangans;

        // Siapkan data wali murid (dengan key Ayah/Ibu)
        $waliPrefill = $student->waliMurids->keyBy('hubungan');

        return view('bidang.pendidikan.student_edit', compact(
            'student',
            'eduClasses',
            'akunKeuangans',
            'class',
            'waliPrefill'
        ));
    }

    public function update(Request $request, $id)
    {
        \Log::debug('🔥 MASUK KE METHOD UPDATE');

        $validator = Validator::make($request->all(), [
            'no_induk' => 'required|string|unique:students,no_induk,' . $id,
            'nisn' => 'nullable|string|max:9',
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
            'rfid_uid' => 'required|unique:students,rfid_uid,' . $id,
            'edu_class_id' => 'required|exists:edu_classes,id',

            // Wali Murid
            'wali.nama.*' => 'required|string',
            'wali.hubungan.*' => 'required|in:Ayah,Ibu',
            'wali.jenis_kelamin.*' => 'required|in:L,P',
            'wali.nik.*' => 'nullable|string|max:16',
            'wali.no_hp.*' => 'required|string',
            'wali.email.*' => 'required|email',
            'wali.pendidikan_terakhir.*' => 'nullable|string',
            'wali.pekerjaan.*' => 'nullable|string',
            'wali.alamat.*' => 'nullable|string',
            'wali.foto_ktp.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::debug('🚨 VALIDASI GAGAL', $validator->errors()->toArray()); // Tambahkan log ini juga!
            return back()->withErrors($validator)->withInput();
        }

        \Log::debug('✅ VALIDASI LULUS', $request->all());
        \Log::debug('Request Update Student', $request->all());
        \Log::debug('Wali Data', ['wali' => $request->wali]);
        \Log::debug('Uploaded Files', $request->allFiles());

        if (!isset($request->wali['nama']) || !is_array($request->wali['nama'])) {
            throw new \Exception('Data wali tidak valid atau tidak ditemukan.');
        }

        $student = Student::findOrFail($id);

        DB::beginTransaction();
        try {
            // Dokumen siswa
            if ($request->hasFile('pas_photo')) {
                if ($student->pas_photo && Storage::disk('public')->exists($student->pas_photo)) {
                    Storage::disk('public')->delete($student->pas_photo);
                }
                $student->pas_photo = $request->file('pas_photo')->store('students/photo', 'public');
            }

            if ($request->hasFile('akte')) {
                if ($student->akte && Storage::disk('public')->exists($student->akte)) {
                    Storage::disk('public')->delete($student->akte);
                }
                $student->akte = $request->file('akte')->store('students/akte', 'public');
            }

            if ($request->hasFile('kk')) {
                if ($student->kk && Storage::disk('public')->exists($student->kk)) {
                    Storage::disk('public')->delete($student->kk);
                }
                $student->kk = $request->file('kk')->store('students/kk', 'public');
            }

            // Log sebelum update
            \Log::info('Before update student', $student->toArray());

            // Update data siswa
            $student->update([
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
            ]);

            \Log::info('After update student', $student->fresh()->toArray());

            // Simpan/Update wali murid
            foreach ($request->wali['nama'] as $i => $nama) {
                $hubungan = $request->wali['hubungan'][$i];

                $wali = WaliMurid::where('student_id', $student->id)->where('hubungan', $hubungan)->first();

                $fotoKtp = $wali?->foto_ktp;
                if ($request->hasFile("wali.foto_ktp.$i")) {
                    if ($fotoKtp && Storage::disk('public')->exists($fotoKtp)) {
                        Storage::disk('public')->delete($fotoKtp);
                    }
                    $fotoKtp = $request->file("wali.foto_ktp.$i")->store('wali_ktp', 'public');
                }

                $dataWali = [
                    'student_id' => $student->id,
                    'nama' => $nama,
                    'hubungan' => $hubungan,
                    'jenis_kelamin' => $request->wali['jenis_kelamin'][$i],
                    'nik' => $request->wali['nik'][$i] ?? null,
                    'no_hp' => $request->wali['no_hp'][$i],
                    'email' => $request->wali['email'][$i],
                    'pendidikan_terakhir' => $request->wali['pendidikan_terakhir'][$i] ?? null,
                    'pekerjaan' => $request->wali['pekerjaan'][$i] ?? null,
                    'alamat' => $request->wali['alamat'][$i] ?? null,
                    'foto_ktp' => $fotoKtp,
                ];

                \Log::info("Memproses wali ke-$i", $dataWali);

                if ($wali) {
                    $wali->update($dataWali);
                } else {
                    WaliMurid::create($dataWali);
                }
            }

            DB::commit();
            return redirect()->route('students.index')->with('success', 'Data murid dan wali berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal update siswa', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat memperbarui data.')->withInput();
        }
    }

    public function destroy(Student $student)
    {
        app(StudentFinanceService::class)->deleteWithAllRelations($student);

        return redirect()->route('students.index')->with('success', 'Data siswa dan seluruh relasinya berhasil dihapus.');
    }
}
