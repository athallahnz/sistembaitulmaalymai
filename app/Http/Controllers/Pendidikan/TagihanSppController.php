<?php

namespace App\Http\Controllers\Pendidikan;

use App\Http\Controllers\Controller;
use App\Exports\TagihanSppExport;
use App\Models\EduClass;
use App\Models\Student;
use App\Models\TagihanSpp;
use App\Models\SidebarSetting;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Piutang;
use App\Models\PendapatanBelumDiterima;
use App\Services\RevenueRecognitionService;
use App\Services\StudentPaymentSPPService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TagihanSppController extends Controller
{
    private function rollbackTagihanAccounting(TagihanSpp $tagihan): void
    {
        $studentId = $tagihan->student_id;
        $amount = (int) $tagihan->jumlah;

        // 1) rollback transaksi + ledger asal tagihan (kalau ada transaksi_id)
        if ($tagihan->transaksi_id) {
            $trx = Transaksi::where('id', $tagihan->transaksi_id)
                ->where('sumber', config('sumber.pendapatan_belum_diterima_spp')) // 50011
                ->first();

            if ($trx) {
                Ledger::where('transaksi_id', $trx->id)->delete();
                $trx->delete();
            }
        }

        // 2) kurangi Piutang rekap siswa
        $piutang = Piutang::where('student_id', $studentId)->first();
        if ($piutang) {
            $new = (int) $piutang->jumlah - $amount;
            if ($new <= 0)
                $piutang->delete();
            else
                $piutang->update(['jumlah' => $new, 'status' => 'belum_lunas']);
        }

        // 3) kurangi PBD rekap siswa
        $pbd = PendapatanBelumDiterima::where('student_id', $studentId)->first();
        if ($pbd) {
            $new = (int) $pbd->jumlah - $amount;
            if ($new <= 0)
                $pbd->delete();
            else
                $pbd->update(['jumlah' => $new]);
        }
    }

    public function dashboardTagihan(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan');
        $kelasId = $request->get('kelas');

        // Ambil data kelas untuk filter
        $kelasList = EduClass::all();

        // Ambil semua siswa dengan relasi tagihan
        $students = Student::with([
            'eduClass',
            'tagihanSpps' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun);
                if ($bulan) {
                    $query->where('bulan', $bulan);
                }
            }
        ]);

        if ($kelasId) {
            $students->where('edu_class_id', $kelasId);
        }

        $data = $students->get()->map(function ($s) {
            $s->kelas = $s->eduClass->name ?? '-';
            $s->total_tagihan = $s->tagihanSpps->sum('jumlah');
            $s->total_bayar = $s->tagihanSpps->where('status', 'lunas')->sum('jumlah');
            return $s;
        });


        // Data chart: total tagihan dan pembayaran
        $chartLabels = $data->pluck('name');
        $chartTagihan = $data->pluck('total_tagihan');
        $chartPembayaran = $data->pluck('total_bayar');

        return view('bidang.pendidikan.payments.tagihan_spp.dashboard', compact(
            'tahun',
            'bulan',
            'kelasId',
            'kelasList',
            'data',
            'chartLabels',
            'chartTagihan',
            'chartPembayaran'
        ));
    }

    public function create()
    {
        $classes = EduClass::orderBy('name')->orderBy('tahun_ajaran', 'desc')->get();
        return view('bidang.pendidikan.payments.tagihan_spp.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'edu_class_ids' => 'required|array',
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric|min:2020',
            'jumlah' => 'required|numeric|min:1000',
            'tanggal_aktif' => 'required|date',
        ]);

        $students = Student::whereIn('edu_class_id', $request->edu_class_ids)->get();

        foreach ($students as $student) {

            $tagihan = TagihanSpp::updateOrCreate([
                'student_id' => $student->id,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
            ], [
                'jumlah' => $request->jumlah,
                'status' => 'belum_lunas',
                'tanggal_aktif' => Carbon::parse($request->tanggal_aktif),
            ]);

            // Buat transaksi+ledger+piutang+pbd dan simpan transaksi_id ke tagihan
            $trx = app(\App\Services\StudentFinanceService::class)
                ->handleNewStudentSPPFinance($student, (int) $request->jumlah, (int) $request->bulan, (int) $request->tahun);

            $tagihan->update(['transaksi_id' => $trx->id]);
        }

        return redirect()->back()->with('success', 'Tagihan berhasil dibuat untuk semua siswa.');
    }

    public function getData(Request $request)
    {
        $tahun = $request->tahun;
        $bulan = $request->bulan;
        $kelas = $request->kelas;

        $students = Student::with('eduClass');

        if ($kelas) {
            $students->where('edu_class_id', $kelas);
        }

        $students = $students->get()->map(function ($student) use ($tahun, $bulan) {
            $tagihanQuery = TagihanSpp::where('student_id', $student->id);
            $bayarQuery = TagihanSpp::where('student_id', $student->id)->where('status', 'lunas');

            if ($tahun) {
                $tagihanQuery->where('tahun', $tahun);
                $bayarQuery->where('tahun', $tahun);
            }

            if ($bulan) {
                $tagihanQuery->where('bulan', $bulan);
                $bayarQuery->where('bulan', $bulan);
            }

            $student->total_tagihan = (float) $tagihanQuery->sum('jumlah');
            $student->total_bayar = (float) $bayarQuery->sum('jumlah');

            // ======= FLAG: DELETE PERIODE =======
            // tampilkan delete periode hanya jika filter bulan+tahun aktif dan ada tagihan periode tsb, dan belum ada yang lunas
            $student->can_delete_periode = false;
            if ($tahun && $bulan) {
                $qPeriode = TagihanSpp::where('student_id', $student->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan);

                $hasAnyPeriode = (clone $qPeriode)->exists();
                $hasLunasPeriode = (clone $qPeriode)->where('status', 'lunas')->exists();

                $student->can_delete_periode = $hasAnyPeriode && !$hasLunasPeriode;
            }

            // ======= FLAG: DELETE SEMUA TAGIHAN SISWA =======
            // tampilkan delete student hanya jika tidak ada satupun tagihan lunas
            $hasLunasAny = TagihanSpp::where('student_id', $student->id)
                ->where('status', 'lunas')
                ->exists();

            $student->can_delete_student = !$hasLunasAny;

            return $student;
        });

        return DataTables::of($students)
            ->addColumn('kelas', fn($row) => $row->eduClass->name ?? '-')
            ->addColumn('status', function ($row): string {
                if ($row->total_tagihan > 1) {
                    return ($row->total_bayar >= $row->total_tagihan) ? 'lunas' : 'belum_lunas';
                }
                return 'belum_ada';
            })
            ->addColumn('aksi', function ($row) use ($tahun, $bulan) {
                $detailUrl = e(route('tagihan-spp.show', $row->id));
                $name = e($row->name);

                $deletePeriodeUrl = e(route('tagihan-spp.destroy-periode-student', $row->id));
                $deleteStudentUrl = e(route('tagihan-spp.destroy-student', $row->id));

                $canPeriode = !empty($row->can_delete_periode); // true hanya jika ada periode & belum lunas & ada tagihan
                $canStudent = !empty($row->can_delete_student); // true hanya jika tidak ada lunas sama sekali

                // Filter lengkap?
                $hasPeriodeFilter = !empty($tahun) && !empty($bulan);

                // ===== Build menu items =====
                $menuItems = '';

                // Menu delete periode: tampil jika filter lengkap (atau tampil disabled jika Anda mau)
                if ($hasPeriodeFilter) {
                    if ($canPeriode) {
                        $menuItems .= <<<HTML
                <li>
                <button type="button" class="dropdown-item btn-delete-periode"
                    data-url="{$deletePeriodeUrl}" data-name="{$name}">
                    <i class="bi bi-calendar2-x me-1"></i> Hapus Periode (Filter)
                </button>
                </li>
                HTML;
                    }
                } else {
                    // tampil disabled di dropdown jika filter belum lengkap (UX: user paham kenapa tidak bisa)
                    $menuItems .= <<<HTML
                <li>
                <button type="button" class="dropdown-item" disabled title="Pilih tahun & bulan terlebih dahulu">
                    <i class="bi bi-calendar2-x me-1"></i> Hapus Periode (Filter)
                </button>
                </li>
                HTML;
                }

                if ($canStudent) {
                    if ($menuItems !== '')
                        $menuItems .= '<li><hr class="dropdown-divider"></li>';

                    $menuItems .= <<<HTML
                <li>
                <button type="button" class="dropdown-item text-danger btn-delete-student"
                    data-url="{$deleteStudentUrl}" data-name="{$name}">
                    <i class="bi bi-trash3 me-1"></i> Hapus Semua Tagihan Siswa
                </button>
                </li>
                HTML;
                }

                // Jika tidak ada opsi apapun (mis. canStudent=false dan periode tidak bisa & kita tidak mau tampil disabled)
                // versi ini tetap punya minimal dropdown disabled periode, jadi aman.
                // Namun jika Anda ingin benar-benar hanya Detail saat tidak ada aksi: uncomment block ini
                /*
                if ($menuItems === '') {
                    return <<<HTML
                <a href="{$detailUrl}" class="btn btn-sm btn-info text-white">
                <i class="bi bi-eye"></i> Detail
                </a>
                HTML;
                }
                */

                // ===== Quick delete button behavior =====
                // Default: quick delete = delete periode.
                // - Jika filter belum lengkap -> disabled.
                // - Jika filter lengkap tapi canPeriode=false -> disabled (karena periode tidak bisa dihapus).
                // - Jika canPeriode true -> aktif.
                $quickDisabled = (!$hasPeriodeFilter || !$canPeriode) ? 'disabled' : '';
                $quickTitle = !$hasPeriodeFilter
                    ? 'Pilih tahun & bulan terlebih dahulu'
                    : (!$canPeriode ? 'Tidak dapat hapus periode (sudah lunas / tidak ada tagihan)' : 'Hapus periode sesuai filter');

                // data-url tetap diisi; JS akan cek disabled juga
                $quickDeleteBtn = <<<HTML
                    <button type="button"
                    class="btn btn-sm btn-danger btn-delete-periode"
                    data-url="{$deletePeriodeUrl}" data-name="{$name}"
                    title="{$quickTitle}" {$quickDisabled}>
                    <i class="bi bi-trash"></i>
                    </button>
                    HTML;

                return <<<HTML
                <div class="btn-group" role="group" aria-label="Aksi">
                <a href="{$detailUrl}" class="btn btn-sm btn-info text-white">
                    <i class="bi bi-eye"></i> Detail
                </a>

                {$quickDeleteBtn}

                <button type="button" class="btn btn-sm btn-danger dropdown-toggle-split text-white dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Opsi lainnya">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    {$menuItems}
                </ul>
                </div>
                HTML;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function show($id)
    {
        $student = Student::with('eduClass', 'tagihanSpps')->findOrFail($id);

        return view('bidang.pendidikan.payments.tagihan_spp.show', compact('student'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'tahun' => 'required|numeric',
            'bulan' => 'required|numeric|between:1,12',
            'edu_class_ids' => 'required|array|min:1',
            'edu_class_ids.*' => 'exists:edu_classes,id',
        ]);

        return Excel::download(
            new TagihanSppExport($request->tahun, $request->bulan, $request->edu_class_ids),
            "tagihan_spp_{$request->bulan}_{$request->tahun}.xlsx"
        );
    }

    public function getTagihanByRfid($uid)
    {
        $student = Student::with('eduClass')
            ->where('rfid_uid', $uid)
            ->first();

        if (!$student) {
            return response()->json(null, 404);
        }

        $unpaid = TagihanSpp::where('student_id', $student->id)
            ->where('status', 'belum_lunas')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get(['id', 'bulan', 'tahun', 'jumlah', 'status']);

        if ($unpaid->isEmpty()) {
            return response()->json([
                'message' => 'Semua tagihan sudah lunas',
                'id' => $student->id,
                'name' => $student->name,
                'edu_class' => $student->eduClass?->name,
                'tahun_ajaran' => $student->eduClass?->tahun_ajaran,
                'total_unpaid' => 0,
                'unpaid_items' => [],
            ], 200);
        }

        $totalUnpaid = (float) $unpaid->sum('jumlah');

        // Optional: bikin label bulan (Indonesia)
        $unpaidItems = $unpaid->map(function ($t) {
            $namaBulan = Carbon::create()->month((int) $t->bulan)->translatedFormat('F');
            return [
                'id' => $t->id,
                'bulan' => (int) $t->bulan,
                'tahun' => (int) $t->tahun,
                'bulan_label' => $namaBulan,
                'label' => $namaBulan . ' ' . $t->tahun,
                'jumlah' => (float) $t->jumlah,
            ];
        })->values();

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'edu_class' => $student->eduClass?->name,
            'tahun_ajaran' => $student->eduClass?->tahun_ajaran,
            'total_unpaid' => $totalUnpaid,
            'unpaid_items' => $unpaidItems,
        ], 200);
    }

    public function bayar(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'metode' => 'required|in:tunai,transfer',
            'payment_scope' => 'required|in:single,all',
            'tagihan_ids' => 'required|array|min:1',
            'tagihan_ids.*' => 'integer|exists:tagihan_spps,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Ambil tagihan sesuai pilihan, pastikan milik student & belum lunas
        $tagihans = TagihanSpp::where('student_id', $student->id)
            ->whereIn('id', $request->tagihan_ids)
            ->where('status', 'belum_lunas')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->lockForUpdate()
            ->get();

        if ($tagihans->isEmpty()) {
            return back()->with('error', 'Tagihan tidak ditemukan / sudah lunas.');
        }

        $totalWajib = (int) $tagihans->sum('jumlah');

        // ✅ Tidak lagi percaya input jumlah dari client (anti manipulasi)
        // gunakan total dari database sebagai sumber kebenaran
        DB::transaction(function () use ($student, $request, $tagihans, $totalWajib) {
            StudentPaymentSPPService::paySPP(
                student: $student,
                tagihans: $tagihans,
                metode: $request->metode,
                total: $totalWajib
            );
        });

        $periodeText = $tagihans
            ->map(fn($t) => str_pad($t->bulan, 2, '0', STR_PAD_LEFT) . '/' . $t->tahun)
            ->implode(', ');

        return back()->with('success', "Pembayaran berhasil untuk periode: {$periodeText}");
    }


    public function getChartBulanan(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $kelas = $request->input('kelas');

        Log::info('Chart filter', [
            'tahun' => $tahun,
            'kelas' => $kelas,
            'count_tahun' => TagihanSpp::where('tahun', $tahun)->count(),
            'count_2026' => TagihanSpp::where('tahun', 2026)->count(),
        ]);

        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $tagihanPerBulan = [];
        $pembayaranPerBulan = [];

        $studentsQuery = Student::query();
        if ($kelas) {
            $studentsQuery->where('edu_class_id', $kelas);
        }
        $studentIds = $studentsQuery->pluck('id');

        foreach ($bulanList as $num => $namaBulan) {
            $totalTagihan = TagihanSpp::whereIn('student_id', $studentIds)
                ->where('tahun', $tahun)
                ->where('bulan', $num)
                ->sum('jumlah');

            $totalBayar = TagihanSpp::whereIn('student_id', $studentIds)
                ->where('tahun', $tahun)
                ->where('bulan', $num)
                ->where('status', 'lunas')
                ->sum('jumlah');

            $tagihanPerBulan[] = $totalTagihan;
            $pembayaranPerBulan[] = $totalBayar;
        }

        return response()->json([
            'labels' => array_values($bulanList),
            'tagihan' => $tagihanPerBulan,
            'pembayaran' => $pembayaranPerBulan
        ]);
    }

    public function printReceipt($id)
    {
        $tagihan = TagihanSpp::with('student.eduClass')->findOrFail($id);

        if ($tagihan->status !== 'lunas') {
            abort(403, 'Kwitansi hanya tersedia untuk tagihan yang lunas.');
        }

        $setting = SidebarSetting::first();

        // Logo: ambil dari SidebarSetting kalau ada, kalau tidak pakai default
        $logo = public_path(
            $setting && $setting->logo_path
                ? 'storage/' . $setting->logo_path
                : 'img/photos/logo_yys.png'
        );

        $tahunAjaran = $tagihan->student->eduClass->tahun_ajaran ?? '-';
        $nomorInduk = $tagihan->student->no_induk ?? '-';

        $nomorKwitansi = 'SPP/' . $tahunAjaran . '/' . $nomorInduk . '/' .
            str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT);

        $keterangan = 'Pembayaran SPP bulan ' .
            Carbon::create()->month($tagihan->bulan)->translatedFormat('F');

        $tahunAjaranBersih = str_replace(['/', '\\'], '-', $tahunAjaran);
        $namaSiswaBersih = preg_replace('/[^A-Za-z0-9\-]/', '_', $tagihan->student->name);

        $namaFile = 'SPP-' . $tahunAjaranBersih . '-' .
            $nomorInduk . str_pad($tagihan->bulan, 2, '0', STR_PAD_LEFT) .
            '-' . $namaSiswaBersih . '.pdf';

        // 58mm slip → ±164pt, tinggi 600pt (cukup untuk kwitansi)
        $paperSize = [0, 0, 250, 400];

        $pdf = Pdf::loadView(
            'bidang.pendidikan.payments.tagihan_spp.kwitansi-per-pembayaran',
            [
                'tagihan' => $tagihan,
                'nomorKwitansi' => $nomorKwitansi,
                'keterangan' => $keterangan,
                'logo' => $logo,
            ]
        )->setPaper($paperSize, 'portrait');

        return $pdf->stream($namaFile);
    }

    public function recognizeStudentSPP(Request $request, Student $student)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Cek dulu: apakah siswa ini punya tagihan SPP LUNAS di bulan/tahun tsb?
        $tagihan = TagihanSpp::where('student_id', $student->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'lunas')
            ->first();

        if (!$tagihan) {
            return back()->with(
                'error',
                "Tidak ada tagihan SPP dengan status LUNAS untuk {$student->name} pada bulan {$bulan}/{$tahun}. Pengakuan pendapatan dibatalkan."
            );
        }

        $already = Transaksi::where('type', 'pengakuan_pendapatan')
            ->where('tagihan_spp_id', $tagihan->id)
            ->exists();

        if ($already) {
            return back()->with('error', "Tagihan SPP {$student->name} bulan {$bulan}/{$tahun} sudah pernah diakui. Proses dibatalkan.");
        }

        // Kalau lolos, baru lakukan pengakuan pendapatan
        RevenueRecognitionService::recognizeSPP($student, $bulan, $tahun);

        return back()->with(
            'success',
            "Pengakuan pendapatan SPP {$student->name} bulan {$bulan}/{$tahun} berhasil."
        );
    }

    public function recognizeSPPBulk(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Ambil semua tagihan LUNAS di periode tsb
        $tagihanLunas = TagihanSpp::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'lunas')
            ->select(['id', 'student_id', 'jumlah'])
            ->get();

        if ($tagihanLunas->isEmpty()) {
            return back()->with(
                'error',
                "Tidak ada siswa dengan tagihan SPP LUNAS pada bulan {$bulan}/{$tahun}."
            );
        }

        // Map: student_id => tagihan_id (jika 1 student hanya 1 tagihan per bulan)
        // Kalau ada kemungkinan dobel, Anda bisa groupBy student_id dan ambil first().
        $studentIds = $tagihanLunas->pluck('student_id')->unique()->values();

        // Cek yang sudah diakui: transaksi pengakuan untuk tagihan-tagihan tsb
        $recognizedTagihanIds = Transaksi::where('type', 'pengakuan_pendapatan')
            ->whereIn('tagihan_spp_id', $tagihanLunas->pluck('id'))
            ->pluck('tagihan_spp_id')
            ->unique();

        // Filter tagihan yang belum diakui
        $tagihanToProcess = $tagihanLunas->whereNotIn('id', $recognizedTagihanIds);

        $skipped = $tagihanLunas->count() - $tagihanToProcess->count();

        if ($tagihanToProcess->isEmpty()) {
            return back()->with(
                'error',
                "Semua tagihan SPP LUNAS bulan {$bulan}/{$tahun} sudah pernah diakui. Tidak ada data yang diproses."
            );
        }

        DB::transaction(function () use ($tagihanToProcess, $bulan, $tahun) {
            // Ambil siswa untuk tagihan yang akan diproses
            $students = Student::whereIn('id', $tagihanToProcess->pluck('student_id'))
                ->with('eduClass')
                ->get()
                ->keyBy('id');

            foreach ($tagihanToProcess as $tagihan) {
                $student = $students->get($tagihan->student_id);
                if (!$student) continue;

                // Panggil service (pastikan service Anda juga set tagihan_spp_id pada Transaksi)
                RevenueRecognitionService::recognizeMonthlySPP($student, $bulan, $tahun);
            }
        });

        $processed = $tagihanToProcess->count();

        return back()->with(
            'success',
            "Pengakuan pendapatan SPP BULK {$bulan}/{$tahun} berhasil. Diproses: {$processed} tagihan. Dilewati (sudah diakui): {$skipped} tagihan."
        );
    }

    public function history()
    {
        $kelasList = EduClass::orderBy('name')->orderBy('tahun_ajaran', 'desc')->get();

        return view('bidang.pendidikan.payments.tagihan_spp.history', compact('kelasList'));
    }

    /**
     * DataTables utama: 1 baris per siswa (filtered by kelas_id)
     * Menampilkan ringkasan total, dan tombol expand untuk child row.
     */
    public function historyStudentsData(Request $request)
    {
        $kelasId = $request->get('kelas_id');
        $tahun = $request->get('tahun'); // optional, untuk ringkasannya
        $bulan = $request->get('bulan'); // optional, untuk ringkasannya

        $q = Student::query()
            ->with('eduClass')
            ->when($kelasId, fn($qq) => $qq->where('edu_class_id', $kelasId))
            ->select('students.*'); // aman untuk DT

        return DataTables::of($q)
            ->addColumn('kelas', fn($row) => $row->eduClass?->name ?? '-')
            ->addColumn('total_tagihan', function ($row) use ($tahun, $bulan) {
                $tq = TagihanSpp::where('student_id', $row->id);
                if ($tahun)
                    $tq->where('tahun', (int) $tahun);
                if ($bulan)
                    $tq->where('bulan', (int) $bulan);
                return (float) $tq->sum('jumlah');
            })
            ->addColumn('total_lunas', function ($row) use ($tahun, $bulan) {
                $tq = TagihanSpp::where('student_id', $row->id)->where('status', 'lunas');
                if ($tahun)
                    $tq->where('tahun', (int) $tahun);
                if ($bulan)
                    $tq->where('bulan', (int) $bulan);
                return (float) $tq->sum('jumlah');
            })
            ->addColumn('total_belum_lunas', function ($row) use ($tahun, $bulan) {
                $tq = TagihanSpp::where('student_id', $row->id)->where('status', 'belum_lunas');
                if ($tahun)
                    $tq->where('tahun', (int) $tahun);
                if ($bulan)
                    $tq->where('bulan', (int) $bulan);
                return (float) $tq->sum('jumlah');
            })
            ->editColumn('total_tagihan', fn($row) => number_format((float) $row->total_tagihan, 0, ',', '.'))
            ->editColumn('total_lunas', fn($row) => number_format((float) $row->total_lunas, 0, ',', '.'))
            ->editColumn('total_belum_lunas', fn($row) => number_format((float) $row->total_belum_lunas, 0, ',', '.'))
            ->addColumn(
                'aksi',
                fn($row) =>
                '<button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-child">
                    <i class="bi bi-chevron-down"></i> Detail
                </button>'
            )
            ->rawColumns(['aksi'])
            ->make(true);
    }

    /**
     * Data untuk child row: daftar tagihan milik 1 siswa
     * Optional filter: tahun/bulan/status
     */

    public function historyStudentItems(Request $request, Student $student)
    {
        $tahun = $request->get('tahun');
        $bulan = $request->get('bulan');
        $status = $request->get('status'); // lunas / belum_lunas (optional)

        $q = TagihanSpp::where('student_id', $student->id)->orderBy('tahun')->orderBy('bulan');

        if ($tahun)
            $q->where('tahun', (int) $tahun);
        if ($bulan)
            $q->where('bulan', (int) $bulan);
        if ($status)
            $q->where('status', $status);

        $items = $q->get()->map(function ($t) {
            $bulanLabel = Carbon::create()->month((int) $t->bulan)->translatedFormat('F');

            return [
                'id' => $t->id,
                'periode' => $bulanLabel . ' ' . $t->tahun,
                'jumlah' => (float) $t->jumlah,
                'jumlah_label' => number_format((float) $t->jumlah, 0, ',', '.'),
                'status' => $t->status,
                'tanggal_aktif' => optional($t->tanggal_aktif)->format('Y-m-d'),
                'can_delete' => $t->status !== 'lunas',
                'delete_url' => route('tagihan-spp.destroy-item', $t->id),
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    /**
     * Edit massal: 1 periode (bulan/tahun) untuk semua siswa 1 kelas.
     * Default aman: hanya update/create yang statusnya belum_lunas.
     */
    public function bulkEditPeriodeByKelas(Request $request)
    {
        $request->validate([
            'edu_class_id' => 'required|exists:edu_classes,id',
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'jumlah' => 'required|numeric|min:1000',
            'tanggal_aktif' => 'required|date',
            'scope' => 'required|in:belum_lunas_saja,semua', // kontrol safety
        ]);

        $kelasId = (int) $request->edu_class_id;
        $tahun = (int) $request->tahun;
        $bulan = (int) $request->bulan;
        $jumlah = (float) $request->jumlah;
        $tanggalAktif = Carbon::parse($request->tanggal_aktif);
        $scope = $request->scope;

        $students = Student::where('edu_class_id', $kelasId)->get(['id', 'name']);

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa pada kelas tersebut.'
            ], 422);
        }

        $updated = 0;
        $created = 0;
        $skippedLunas = 0;

        DB::transaction(function () use ($students, $tahun, $bulan, $jumlah, $tanggalAktif, $scope, &$updated, &$created, &$skippedLunas) {
            foreach ($students as $student) {

                $existing = TagihanSpp::where('student_id', $student->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'lunas' && $scope === 'belum_lunas_saja') {
                        $skippedLunas++;
                        continue;
                    }

                    $existing->update([
                        'jumlah' => $jumlah,
                        'tanggal_aktif' => $tanggalAktif,
                        // status jangan dipaksa berubah di bulk edit (lebih aman)
                        // 'status' => $existing->status,
                    ]);
                    $updated++;
                } else {
                    TagihanSpp::create([
                        'student_id' => $student->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'jumlah' => $jumlah,
                        'status' => 'belum_lunas',
                        'tanggal_aktif' => $tanggalAktif,
                    ]);

                    // Jika Anda ingin otomatis bikin jurnal/piutang untuk bulk edit periode baru,
                    // panggil service Anda di sini (opsional, tergantung desain akuntansi):
                    // app(\App\Services\StudentFinanceService::class)
                    //     ->handleNewStudentSPPFinance($student, $jumlah, $bulan, $tahun);

                    $created++;
                }
            }
        });

        $msg = "Bulk edit selesai. Updated: {$updated}, Created: {$created}";
        if ($skippedLunas > 0)
            $msg .= ", Skipped Lunas: {$skippedLunas} (scope: belum_lunas_saja)";

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    public function historyStudentsMobile(Request $request)
    {
        $kelasId = $request->get('kelas_id');
        $tahun = $request->get('tahun');
        $bulan = $request->get('bulan');

        if (!$kelasId) {
            return response()->json(['success' => false, 'message' => 'kelas_id wajib'], 422);
        }

        $students = Student::with('eduClass')
            ->where('edu_class_id', $kelasId)
            ->orderBy('name')
            ->get()
            ->map(function ($s) use ($tahun, $bulan) {
                $tq = TagihanSpp::where('student_id', $s->id);
                $lq = TagihanSpp::where('student_id', $s->id)->where('status', 'lunas');
                $bq = TagihanSpp::where('student_id', $s->id)->where('status', 'belum_lunas');

                if ($tahun) {
                    $tq->where('tahun', (int) $tahun);
                    $lq->where('tahun', (int) $tahun);
                    $bq->where('tahun', (int) $tahun);
                }
                if ($bulan) {
                    $tq->where('bulan', (int) $bulan);
                    $lq->where('bulan', (int) $bulan);
                    $bq->where('bulan', (int) $bulan);
                }

                $totalTagihan = (float) $tq->sum('jumlah');
                $totalLunas = (float) $lq->sum('jumlah');
                $totalBelum = (float) $bq->sum('jumlah');

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'kelas' => $s->eduClass?->name ?? '-',
                    'total_tagihan' => $totalTagihan,
                    'total_lunas' => $totalLunas,
                    'total_belum_lunas' => $totalBelum,
                    'total_tagihan_label' => number_format($totalTagihan, 0, ',', '.'),
                    'total_lunas_label' => number_format($totalLunas, 0, ',', '.'),
                    'total_belum_lunas_label' => number_format($totalBelum, 0, ',', '.'),
                ];
            });

        return response()->json([
            'success' => true,
            'kelas_id' => (int) $kelasId,
            'items' => $students,
        ]);
    }

    public function destroyItem(TagihanSpp $tagihanSpp)
    {
        if ($tagihanSpp->status === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa dihapus: tagihan sudah LUNAS.'
            ], 422);
        }

        DB::transaction(function () use ($tagihanSpp) {
            $this->rollbackTagihanAccounting($tagihanSpp);
            $tagihanSpp->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil dihapus dan jurnal/rekap terkait berhasil di-roll back.'
        ]);
    }

    public function destroyPeriodeByClass(Request $request, EduClass $eduClass)
    {
        $tahun = (int) $request->get('tahun');
        $bulan = (int) $request->get('bulan');

        if (!$tahun || !$bulan) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun dan bulan wajib dipilih.'
            ], 422);
        }

        // semua student di kelas
        $studentIds = Student::where('edu_class_id', $eduClass->id)->pluck('id');

        $base = TagihanSpp::whereIn('student_id', $studentIds)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan);

        if (!(clone $base)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tagihan pada periode tersebut untuk kelas ini.'
            ], 422);
        }

        // blok kalau ada lunas
        $hasLunas = (clone $base)->where('status', 'lunas')->exists();
        if ($hasLunas) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa dihapus: ada tagihan LUNAS pada periode tersebut dalam kelas ini.'
            ], 422);
        }

        $deletedCount = 0;

        DB::transaction(function () use ($base, &$deletedCount) {
            // ambil semua item belum_lunas dan rollback per item
            $items = (clone $base)->where('status', 'belum_lunas')->get();

            foreach ($items as $tagihan) {
                $this->rollbackTagihanAccounting($tagihan);
                $tagihan->delete();
                $deletedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus {$deletedCount} tagihan periode {$bulan}/{$tahun} untuk kelas ini."
        ]);
    }
}
