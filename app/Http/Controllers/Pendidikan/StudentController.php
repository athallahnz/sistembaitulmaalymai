<?php

namespace App\Http\Controllers\Pendidikan;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Bidang;
use App\Models\WaliMurid;
use App\Models\StudentCost;
use App\Models\EduClass;
use App\Models\AkunKeuangan;
use App\Models\PendapatanBelumDiterima;
use App\Models\Piutang;
use App\Models\Transaksi;
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
        $eduClasses = EduClass::orderBy('name')->orderBy('tahun_ajaran', 'desc')->get();
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

    public function store(request $request)
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
                    'ttl' => Carbon::createFromFormat('d/m/Y', $request->ttl)->format('Y-m-d'),
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
            app(StudentFinanceService::class)->handleNewStudentFinance($student, $biayaPairs);

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
        $q = Student::query()
            ->leftJoin('edu_classes as ec', 'ec.id', '=', 'students.edu_class_id')
            ->select([
                'students.*',
                'ec.name as ec_name',
                'ec.tahun_ajaran as ec_tahun',
            ]);

        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $showUrl = route('students.show', $row->id);
                $editUrl = route('students.edit', $row->id);
                $deleteUrl = route('students.destroy', $row->id);

                return <<<HTML
                <a href="{$showUrl}" class="btn btn-info btn-sm me-2 mb-2"><i class="bi bi-eye"></i></a>
                <a href="{$editUrl}" class="btn btn-warning btn-sm me-2 mb-2"><i class="bi bi-pencil-square"></i></a>
                <button type="button" class="btn btn-danger btn-sm mb-2 btn-hapus" data-url="{$deleteUrl}">
                    <i class="bi bi-trash"></i>
                </button>
                <form id="delete-form" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            HTML;
            })
            ->addColumn('kelas', function ($row) {
                return ($row->ec_name ? $row->ec_name : '-') . ($row->ec_tahun ? ' - ' . $row->ec_tahun : '');
            })
            // agar global search ke "kelas" jalan
            ->filterColumn('kelas', function ($query, $keyword) {
                $query->where(function ($qq) use ($keyword) {
                    $qq->where('ec.name', 'like', "%{$keyword}%")
                        ->orWhere('ec.tahun_ajaran', 'like', "%{$keyword}%");
                });
            })
            // agar sort kolom "kelas" jalan
            ->orderColumn('kelas', function ($query, $order) {
                $query->orderBy('ec.name', $order)->orderBy('ec.tahun_ajaran', $order);
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
        // Eager load relasi yang diperlukan
        $student->load(['costs.akunKeuangan', 'waliMurids', 'eduClass.akunKeuangans']);

        // === 1) Normalisasi TTL agar cocok untuk <input type="date"> (yyyy-MM-dd) ===
        $ttlValue = null;
        if (!empty($student->ttl)) {
            try {
                $ttlValue = Carbon::parse($student->ttl)->format('Y-m-d');
            } catch (\Throwable $e) {
                $ttlValue = null;
            }
        }

        // === 2) Seragamkan struktur Wali (Ayah & Ibu) ===
        $ayah = $student->waliMurids->firstWhere('hubungan', 'Ayah');
        $ibu = $student->waliMurids->firstWhere('hubungan', 'Ibu');

        // Untuk Blade lama
        $waliPrefill = $student->waliMurids->keyBy('hubungan');

        // Untuk array baru (Ayah dan Ibu)
        $waliForm = [
            [
                'id' => $ayah->id ?? null,
                'hubungan' => 'Ayah',
                'nama' => $ayah->nama ?? '',
                'jenis_kelamin' => $ayah->jenis_kelamin ?? 'L',
                'nik' => $ayah->nik ?? '',
                'no_hp' => $ayah->no_hp ?? '',
                'email' => $ayah->email ?? '',
                'pendidikan_terakhir' => $ayah->pendidikan_terakhir ?? '',
                'pekerjaan' => $ayah->pekerjaan ?? '',
                'alamat' => $ayah->alamat ?? '',
                'foto_ktp_url' => isset($ayah->foto_ktp) ? asset('storage/' . $ayah->foto_ktp) : null,
            ],
            [
                'id' => $ibu->id ?? null,
                'hubungan' => 'Ibu',
                'nama' => $ibu->nama ?? '',
                'jenis_kelamin' => $ibu->jenis_kelamin ?? 'P',
                'nik' => $ibu->nik ?? '',
                'no_hp' => $ibu->no_hp ?? '',
                'email' => $ibu->email ?? '',
                'pendidikan_terakhir' => $ibu->pendidikan_terakhir ?? '',
                'pekerjaan' => $ibu->pekerjaan ?? '',
                'alamat' => $ibu->alamat ?? '',
                'foto_ktp_url' => isset($ibu->foto_ktp) ? asset('storage/' . $ibu->foto_ktp) : null,
            ],
        ];

        // === 3) Data biaya
        $costs = $student->costs
            ->map(fn($c) => [
                'id' => $c->id,
                'akun_keuangan_id' => $c->akun_keuangan_id,
                'akun_keuangan_nama' => optional($c->akunKeuangan)->nama,
                'jumlah' => $c->jumlah,
            ])
            ->values();

        // === 4) Data dropdown kelas & akun keuangan
        $eduClasses = EduClass::orderBy('tahun_ajaran', 'desc')->get();
        $class = $student->eduClass;
        $akunKeuangans = $class?->akunKeuangans ?? collect();

        // 🧩 Tambahkan Log untuk memastikan data terkirim
        Log::info('=== DEBUG EDIT STUDENT ===', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'ttl_raw' => $student->ttl,
            'ttlValue' => $ttlValue,
            'wali_count' => $student->waliMurids->count(),
            'wali_keys' => $student->waliMurids->pluck('hubungan'),
            'waliForm' => $waliForm,
            'costs_count' => $costs->count(),
            'class' => optional($class)->name,
        ]);

        return view('bidang.pendidikan.student_edit', compact(
            'student',
            'ttlValue',
            'eduClasses',
            'class',
            'akunKeuangans',
            'waliForm',
            'waliPrefill',
            'costs'
        ));
    }

    public function update (Request $request, $id)
    {
        \Log::debug('🔥 MASUK KE METHOD UPDATE');

        // === A) Normalisasi TTL ke Y-m-d sebelum validasi ===
        if ($request->filled('ttl')) {
            try {
                $ttlRaw = $request->input('ttl');
                if (is_string($ttlRaw) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $ttlRaw)) {
                    $request->merge([
                        'ttl' => Carbon::createFromFormat('d/m/Y', $ttlRaw)->format('Y-m-d'),
                    ]);
                } else {
                    $request->merge([
                        'ttl' => Carbon::parse($ttlRaw)->format('Y-m-d'),
                    ]);
                }
            } catch (\Throwable $e) {
                return back()->withErrors(['ttl' => 'Format tanggal lahir tidak valid.'])->withInput();
            }
        }

        // === B) Validasi utama ===
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

            // (Opsional) Validasi rincian biaya bila disediakan
            // Kita terima dua skema nama field:
            // - baru: akun_keuangan_id[] + jumlah[]
            // - lama: biaya[akun_id][] + biaya[nominal][]
            'akun_keuangan_id' => 'sometimes|array|min:1',
            'akun_keuangan_id.*' => 'sometimes|distinct|exists:akun_keuangans,id',
            'jumlah' => 'sometimes|array|min:1',
            'jumlah.*' => 'sometimes|numeric|min:0',
            'biaya.akun_id' => 'sometimes|array|min:1',
            'biaya.akun_id.*' => 'sometimes|distinct|exists:akun_keuangans,id',
            'biaya.nominal' => 'sometimes|array|min:1',
            'biaya.nominal.*' => 'sometimes',
            'biaya.akun_id.*.distinct' => 'Terdapat akun biaya yang sama. Mohon pastikan setiap akun keuangan hanya dipilih sekali.',
        ]);

        if ($validator->fails()) {
            \Log::debug('🚨 VALIDASI GAGAL', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }
        // ✅ VALIDASI LULUS, lanjut cek duplikasi manual
        if ($request->filled('biaya.akun_id')) {
            $akunIds = $request->input('biaya.akun_id');
            if (count($akunIds) !== count(array_unique($akunIds))) {
                return back()
                    ->withErrors(['biaya.akun_id' => 'Terdapat akun keuangan ganda dalam rincian biaya.'])
                    ->withInput();
            }
        }
        \Log::debug('✅ VALIDASI LULUS', $request->all());
        \Log::debug('Request Update Student', $request->all());
        \Log::debug('Wali Data', ['wali' => $request->wali]);
        \Log::debug('Uploaded Files', $request->allFiles());

        if (!isset($request->wali['nama']) || !is_array($request->wali['nama'])) {
            throw new \Exception('Data wali tidak valid atau tidak ditemukan.');
        }

        // === C) Ambil pasangan biaya (fallback utk nama field lama), lalu hitung total ===
        $akunIds = $request->input('akun_keuangan_id', []);
        $jumlahs = $request->input('jumlah', []);

        if (empty($akunIds) && $request->filled('biaya.akun_id')) {
            $akunIds = $request->input('biaya.akun_id', []);
        }
        if (empty($jumlahs) && $request->filled('biaya.nominal')) {
            $jumlahs = $request->input('biaya.nominal', []);
        }

        // Normalisasi ke pasangan [akun_id => jumlah_int]
        $biayaPairs = [];
        foreach ($akunIds as $idx => $akunId) {
            if ($akunId === null || $akunId === '')
                continue;
            $raw = $jumlahs[$idx] ?? 0;
            // bersihkan selain digit (handle "1.000.000" atau "Rp 1.000.000")
            $val = (int) preg_replace('/\D+/', '', (string) $raw);
            $biayaPairs[(int) $akunId] = $val;
        }

        // Hitung total dari pasangan
        $totalBiaya = array_sum($biayaPairs);
        $request->merge(['total_biaya' => $totalBiaya]); // override agar konsisten dengan detail

        // (Opsional batas aman BIGINT)
        if ($totalBiaya > 9223372036854775807) {
            return back()->withErrors(['total_biaya' => 'Total biaya terlalu besar.'])->withInput();
        }

        $student = Student::findOrFail($id);
        $oldTotalBiaya = (int) ($student->total_biaya ?? 0); // simpan total lama untuk hitung delta
        DB::beginTransaction();
        try {
            // === D) Upload dokumen siswa (jika ada penggantian) ===
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

            \Log::info('Before update student', $student->toArray());

            // === E) Update data siswa ===
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
                'total_biaya' => $totalBiaya,
            ]);

            \Log::info('After update student', $student->fresh()->toArray());

            // === F) Sinkronisasi Wali Murid (Ayah/Ibu) ===
            foreach ($request->wali['nama'] as $i => $nama) {
                $hubungan = $request->wali['hubungan'][$i];

                $wali = WaliMurid::where('student_id', $student->id)
                    ->where('hubungan', $hubungan)
                    ->first();

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

            // === G) Sinkronisasi Rincian Biaya (student_costs) ===
            // Strategi: update atau create yang ada di request, lalu hapus biaya yang tidak lagi dipilih.
            $existingCosts = StudentCost::where('student_id', $student->id)->get()->keyBy('akun_keuangan_id');
            $keepAkunIds = [];

            foreach ($biayaPairs as $akunId => $nominal) {
                $keepAkunIds[] = (int) $akunId;

                if ($existingCosts->has($akunId)) {
                    $cost = $existingCosts->get($akunId);
                    if ((int) $cost->jumlah !== (int) $nominal) {
                        $cost->update(['jumlah' => (int) $nominal]);
                    }
                } else {
                    StudentCost::create([
                        'student_id' => $student->id,
                        'akun_keuangan_id' => (int) $akunId,
                        'jumlah' => (int) $nominal,
                    ]);
                }
            }

            // === H) Overwrite Pendapatan Belum Diterima (PBD) by student_id ===
            // Kita tidak create entry baru; hanya update baris yang sudah ada.
            $DEFAULT_PBD_BIDANG_NAME = 'Pendidikan'; // ubah jika nama bidang berbeda

            // Ambil id bidang "Keuangan" (opsional, agar lebih spesifik)
            $bidangId = Bidang::where('name', $DEFAULT_PBD_BIDANG_NAME)->value('id'); // bisa null jika tidak ada

            // Cari baris PBD untuk student ini (filter bidang jika ada)
            $pbdQuery = PendapatanBelumDiterima::where('student_id', $student->id);
            if ($bidangId) {
                $pbdQuery->where('bidang_name', $bidangId);
            }

            // Ambil baris yang akan di-update (kalau ada)
            $pbdRow = $pbdQuery->orderBy('id', 'asc')->first();

            if ($pbdRow) {
                // Overwrite jumlah = totalBiaya saat ini
                $pbdRow->update([
                    'jumlah' => number_format((float) $totalBiaya, 2, '.', ''), // DECIMAL(15,2)
                    'tanggal_pencatatan' => now()->toDateString(),
                    'deskripsi' => 'Overwrite saldo PBD pada update data siswa ID ' . $student->id,
                    // optional: simpan juga user yang mengubah (kalau kolom boleh null)
                    'user_id' => auth()->id(),
                    // 'bidang_name'     => $bidangId, // aktifkan jika ingin sekaligus “meluruskan” bidang
                ]);

                \Log::info('PBD overwritten', [
                    'pbd_id' => $pbdRow->id,
                    'student_id' => $student->id,
                    'bidang_id' => $bidangId,
                    'jumlah' => $totalBiaya,
                ]);
            } else {
                // Sesuai permintaan: JANGAN create bila tidak ada baris.
                \Log::warning('PBD row not found; skipped overwrite (no create as requested)', [
                    'student_id' => $student->id,
                    'bidang_id' => $bidangId,
                ]);
            }

            // === I) Overwrite Piutang by student_id (tanpa create) ===
            // Catatan akun piutang dari kamu: 1032 (Piutang PMB)
            $PIUTANG_AKUN_ID = 1032;

            // Query piutang terbuka (belum lunas) untuk student ini & akun piutang PMB
            $piutangQuery = Piutang::where('student_id', $student->id)
                ->where('akun_keuangan_id', $PIUTANG_AKUN_ID)
                ->where('status', 'belum_lunas')
                ->orderBy('id', 'asc');

            $openCount = (clone $piutangQuery)->count();
            $piutangRow = $piutangQuery->first();

            if ($piutangRow) {
                // Overwrite jumlah jadi totalBiaya saat ini (DECIMAL(15,2))
                $piutangRow->update([
                    'jumlah' => number_format((float) $totalBiaya, 2, '.', ''),
                    // tidak ubah tanggal_jatuh_tempo (NOT NULL), biarkan apa adanya
                    'deskripsi' => 'Overwrite saldo Piutang saat update data siswa ID ' . $student->id,
                    'user_id' => auth()->id(),
                    // opsional: ikutkan bidang_name sama dengan PBD yang sudah dicari sebelumnya
                    'bidang_name' => $bidangId ?? $piutangRow->bidang_name,
                ]);

                \Log::info('Piutang overwritten', [
                    'piutang_id' => $piutangRow->id,
                    'student_id' => $student->id,
                    'open_rows' => $openCount,
                    'jumlah' => $totalBiaya,
                ]);

                if ($openCount > 1) {
                    \Log::warning('Multiple open piutang rows detected; only first row overwritten', [
                        'student_id' => $student->id,
                        'akun_id' => $PIUTANG_AKUN_ID,
                        'open_rows' => $openCount,
                    ]);
                }
            } else {
                // Sesuai permintaan: jangan create kalau tidak ada baris
                \Log::warning('No open piutang row found; skipped overwrite (no create as requested)', [
                    'student_id' => $student->id,
                    'akun_id' => $PIUTANG_AKUN_ID,
                ]);
            }
            // === J) Overwrite Transaksi (type='pendapatan belum diterima') berdasar student_name ===
            // Catatan: kita TIDAK membuat transaksi baru; hanya update baris yang sudah ada.
            // Kriteria: type='pendapatan belum diterima' dan deskripsi LIKE %student_name%
            // Opsional: filter bidang_name agar lebih spesifik (menggunakan $bidangId dari blok PBD)

            $trxQuery = Transaksi::where('type', 'pendapatan belum diterima')
                ->where('deskripsi', 'like', '%' . $student->name . '%');

            if (!empty($bidangId)) {
                $trxQuery->where('bidang_name', $bidangId);
            }

            // Ambil transaksi yang paling terbaru (berdasarkan tanggal & id)
            $trxCount = (clone $trxQuery)->count();
            $trxRow = $trxQuery->orderBy('tanggal_transaksi', 'desc')->orderBy('id', 'desc')->first();

            if ($trxRow) {
                // Jangan ubah tanggal/kode/saldo agar histori ledger tetap aman.
                // Kita overwrite amount dan pastikan deskripsi masih memuat nama siswa.
                $newAmount = number_format((float) $totalBiaya, 2, '.', '');
                $newDescBase = $trxRow->deskripsi ?? '';
                // Pastikan nama siswa tetap ada di deskripsi:
                if (stripos($newDescBase, $student->name) === false) {
                    $newDescBase = trim($newDescBase . ' - ' . $student->name);
                }
                // Tambahkan penanda update (opsional)
                $newDesc = $newDescBase . ' (overwrite via update siswa ID ' . $student->id . ')';

                $trxRow->update([
                    'amount' => $newAmount,
                    'saldo' => $newAmount, // <<-- HINDARI mengubah saldo di sini kecuali kamu yakin aturan perhitungan saldo
                    'deskripsi' => $newDesc,
                    // 'bidang_name' => $bidangId ?? $trxRow->bidang_name, // konsistenkan bidang bila perlu
                    // 'akun_keuangan_id' => 203, // hanya setel ini kalau memang desain transaksinya refer ke akun PBD; otherwise biarkan
                    // 'sumber' => $student->id,  // kalau kolom 'sumber' kamu gunakan untuk referensi entitas, bisa diisi ID siswa
                ]);

                \Log::info('Transaksi PBD overwritten', [
                    'transaksi_id' => $trxRow->id,
                    'student_id' => $student->id,
                    'matched_rows' => $trxCount,
                    'amount' => $newAmount,
                ]);

                if ($trxCount > 1) {
                    \Log::warning('Multiple transaksi rows matched; only the latest was overwritten', [
                        'student_id' => $student->id,
                        'matched_rows' => $trxCount,
                    ]);
                }
            } else {
                // Sesuai instruksi: JANGAN create jika tidak ketemu
                \Log::warning('No transaksi row matched; skip overwrite (no create)', [
                    'student_id' => $student->id,
                    'type' => 'pendapatan belum diterima',
                    'contains' => $student->name,
                    'bidang_id' => $bidangId ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('students.index')->with('success', 'Data murid, wali, dan rincian biaya berhasil diperbarui.');
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
