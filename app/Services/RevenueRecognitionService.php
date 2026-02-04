<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Student;
use App\Models\EduPayment;
use App\Models\TagihanSpp;
use App\Models\StudentCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueRecognitionService
{
    /**
     * Pengakuan pendapatan SPP untuk satu siswa di 1 periode (bulan/tahun).
     * Dipakai oleh controller recognizeStudentSPP().
     */
    public static function recognizeSPP(
        Student $student,
        int $bulan,
        int $tahun,
        ?Carbon $tanggal = null
    ): void {
        // delegasikan ke fungsi utama
        self::recognizeMonthlySPP($student, $bulan, $tahun, $tanggal);
    }

    /**
     * Pengakuan pendapatan SPP untuk satu siswa di 1 periode (bulan/tahun).
     * Dipakai juga oleh proses BULK.
     *
     * Alur jurnal:
     *   D Pendapatan Belum Diterima – SPP (50011)
     *       K Pendapatan SPP (202 / 2021 / 2022) tergantung kelas siswa
     *
     * HANYA untuk tagihan SPP yang status-nya 'lunas'.
     */
    public static function recognizeMonthlySPP(Student $student, int $bulan, int $tahun, ?Carbon $tanggal = null): void
    {
        DB::transaction(function () use ($student, $bulan, $tahun, $tanggal) {

            $akunPBDSPP = config('akun.pendapatan_belum_diterima_spp');
            $akunPendSPP = self::resolveAkunPendapatanSPP($student);

            // LOCK row agar aman dari double-click / race condition
            $tagihan = TagihanSpp::where('student_id', $student->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('status', 'lunas')
                ->lockForUpdate()
                ->first();

            if (!$tagihan || $tagihan->jumlah <= 0) {
                return;
            }

            // ✅ idempotency check: jika sudah pernah dibuat transaksi pengakuan untuk tagihan ini, stop
            $already = Transaksi::where('type', 'pengakuan_pendapatan')
                ->where('tagihan_spp_id', $tagihan->id)
                ->exists();

            if ($already) {
                return; // atau throw exception agar tampil error
            }

            $nominal  = $tagihan->jumlah;
            $tanggal  = $tanggal ?? now();
            $kode     = 'RCG-SPP-' . $tanggal->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
            $deskripsi = "Pengakuan pendapatan SPP {$student->name} bulan {$bulan}/{$tahun}";

            $transaksi = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengakuan_pendapatan',
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $akunPBDSPP,
                'parent_akun_id' => $akunPendSPP,
                'amount' => $nominal,
                'saldo' => 0,
                'bidang_name' => 2,
                'student_id' => $student->id,
                'tagihan_spp_id' => $tagihan->id, // ✅ penting
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPBDSPP,
                'debit' => $nominal,
                'credit' => 0,
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPendSPP,
                'debit' => 0,
                'credit' => $nominal,
            ]);
        });
    }

    /**
     * Menentukan akun pendapatan SPP (202 / 2021 / 2022)
     * berdasarkan nama kelas di relasi eduClass (edu_classes.name).
     *
     * Contoh:
     *  - "KB A", "TK B"      -> 2021 (pendapatan_spp_kb_tk)
     *  - "Daycare A"         -> 2022 (pendapatan_spp_daycare)
     *  - lainnya / tidak ada -> 202  (pendapatan_spp)
     */
    protected static function resolveAkunPendapatanSPP(Student $student): int
    {
        $className = optional($student->eduClass)->name;

        if (!$className) {
            return config('akun.pendapatan_spp'); // 202
        }

        $normalized = strtolower(trim($className));

        if (str_starts_with($normalized, 'daycare')) {
            return config('akun.pendapatan_spp_daycare'); // 2022
        }

        if (str_starts_with($normalized, 'kb') || str_starts_with($normalized, 'tk')) {
            return config('akun.pendapatan_spp_kb_tk');   // 2021
        }

        return config('akun.pendapatan_spp'); // 202 (fallback)
    }

    public static function getRecognizableAmountPMB(Student $student): float
    {
        $totalCost = (float) StudentCost::where('student_id', $student->id)->sum('jumlah'); // integer di DB, aman cast
        $totalPaid = (float) EduPayment::where('student_id', $student->id)->sum('jumlah');  // int

        $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb');

        $totalRecognized = (float) Ledger::query()
            ->join('transaksis', 'transaksis.id', '=', 'ledgers.transaksi_id')
            ->where('transaksis.type', 'pengakuan_pendapatan')
            ->where('transaksis.student_id', $student->id)
            ->where('transaksis.akun_keuangan_id', $akunPBDPMB) // ✅ hanya PMB
            ->sum('ledgers.credit');

        $cap = min($totalPaid, $totalCost);
        $amountToRecognize = $cap - $totalRecognized;

        // kunci 2 desimal
        $amountToRecognize = round($amountToRecognize, 2);

        return max(0, $amountToRecognize);
    }

    public static function previewPMBRecognitionManual(Student $student): array
    {
        $amountToRecognize = self::getRecognizableAmountPMB($student);

        // 1) ambil biaya per akun (group) + urutan berdasarkan id terawal
        $costGroups = StudentCost::query()
            ->selectRaw('akun_keuangan_id, SUM(jumlah) as biaya_total, MIN(id) as sort_id')
            ->where('student_id', $student->id)
            ->groupBy('akun_keuangan_id')
            ->orderBy('sort_id', 'asc')
            ->with(['akunKeuangan:id,kode_akun,nama_akun'])
            ->get();

        // 2) ambil recognized per akun (group)
        $recognizedByAkun = Ledger::query()
            ->selectRaw('ledgers.akun_keuangan_id, SUM(ledgers.credit) as recognized_total')
            ->join('transaksis', 'transaksis.id', '=', 'ledgers.transaksi_id')
            ->where('transaksis.type', 'pengakuan_pendapatan')
            ->where('transaksis.student_id', $student->id)
            ->groupBy('ledgers.akun_keuangan_id')
            ->pluck('recognized_total', 'ledgers.akun_keuangan_id'); // [akun_id => sum]

        $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb');

        $recognizedByAkun = Ledger::query()
            ->selectRaw('ledgers.akun_keuangan_id, SUM(ledgers.credit) as recognized_total')
            ->join('transaksis', 'transaksis.id', '=', 'ledgers.transaksi_id')
            ->where('transaksis.type', 'pengakuan_pendapatan')
            ->where('transaksis.student_id', $student->id)
            ->where('transaksis.akun_keuangan_id', $akunPBDPMB) // ✅ hanya PMB
            ->groupBy('ledgers.akun_keuangan_id')
            ->pluck('recognized_total', 'ledgers.akun_keuangan_id');

        // 3) bentuk list tombol: hanya yang remaining > 0
        $items = [];
        foreach ($costGroups as $g) {
            $akunId = (int) $g->akun_keuangan_id;
            $biaya = (float) $g->biaya_total;
            $rec = (float) ($recognizedByAkun[$akunId] ?? 0);
            $remaining = round($biaya - $rec, 2);

            if ($remaining <= 0) {
                continue; // ✅ tidak muncul jika sudah terpenuhi
            }

            $kode = optional($g->akunKeuangan)->kode_akun;
            $nama = optional($g->akunKeuangan)->nama_akun ?? ('Akun #' . $akunId);
            $label = trim(($kode ? $kode . ' - ' : '') . $nama);

            $items[] = [
                'akun_id' => $akunId,
                'kode_akun' => $kode,
                'nama_akun' => $nama,
                'label_akun' => $label,
                'biaya_total' => $biaya,
                'recognized_total' => $rec,
                'remaining' => $remaining,
            ];
        }

        return [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount_to_recognize' => (float) $amountToRecognize,
            'coa_candidates' => $items, // tombol-tombol
        ];
    }

    public static function recognizePMBManualBySelectedCoa(Student $student, float $amount, int $selectedAkunId, ?Carbon $tanggal = null): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return ['ok' => false, 'message' => 'Nominal pengakuan = 0.'];
        }

        // Ambil biaya per akun (group) + urutan
        $costGroups = StudentCost::query()
            ->selectRaw('akun_keuangan_id, SUM(jumlah) as biaya_total, MIN(id) as sort_id')
            ->where('student_id', $student->id)
            ->groupBy('akun_keuangan_id')
            ->orderBy('sort_id', 'asc')
            ->get();

        if ($costGroups->isEmpty()) {
            return ['ok' => false, 'message' => "Rincian StudentCost tidak ditemukan untuk {$student->name}."];
        }

        // Recognized per akun
        $recognizedByAkun = Ledger::query()
            ->selectRaw('ledgers.akun_keuangan_id, SUM(ledgers.credit) as recognized_total')
            ->join('transaksis', 'transaksis.id', '=', 'ledgers.transaksi_id')
            ->where('transaksis.type', 'pengakuan_pendapatan')
            ->where('transaksis.student_id', $student->id)
            ->groupBy('ledgers.akun_keuangan_id')
            ->pluck('recognized_total', 'ledgers.akun_keuangan_id');

        // Build ordered akun list with remaining
        $ordered = [];
        foreach ($costGroups as $g) {
            $akunId = (int) $g->akun_keuangan_id;
            $biaya = (float) $g->biaya_total;
            $rec = (float) ($recognizedByAkun[$akunId] ?? 0);
            $remaining = round($biaya - $rec, 2);
            if ($remaining > 0) {
                $ordered[] = ['akun_id' => $akunId, 'remaining' => $remaining];
            }
        }

        if (empty($ordered)) {
            return ['ok' => false, 'message' => "Semua POS CoA PMB sudah terpenuhi (tidak ada yang tersisa untuk diakui)."];
        }

        // Jika akun pilihan sudah terpenuhi (race condition), auto pakai akun pertama yang masih ada
        $startIndex = array_search($selectedAkunId, array_column($ordered, 'akun_id'), true);
        if ($startIndex === false) {
            $startIndex = 0;
        }

        // Rotate order: mulai dari akun pilihan
        $rotated = array_merge(array_slice($ordered, $startIndex), array_slice($ordered, 0, $startIndex));

        // Allocation: isi akun 1, lalu lanjut berikutnya jika masih sisa
        $allocations = []; // [akun_id => amount]
        $remainingAmount = $amount;

        foreach ($rotated as $row) {
            if ($remainingAmount <= 0)
                break;

            $akunId = $row['akun_id'];
            $rem = (float) $row['remaining'];

            $take = min($remainingAmount, $rem);
            $take = round($take, 2);

            if ($take <= 0)
                continue;

            $allocations[$akunId] = ($allocations[$akunId] ?? 0) + $take;
            $remainingAmount = round($remainingAmount - $take, 2);
        }

        // Jika masih ada sisa (harusnya tidak terjadi kalau cap benar), blok agar tidak over-recognize
        if ($remainingAmount > 0) {
            // safeguard
            $amount = round($amount - $remainingAmount, 2);
            if ($amount <= 0) {
                return ['ok' => false, 'message' => "Tidak ada alokasi valid untuk pengakuan."];
            }
        }

        // Eksekusi jurnal
        DB::transaction(function () use ($student, $allocations, $amount, $tanggal) {

            $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb');
            $akunPendPMBParent = config('akun.pendapatan_pmb');

            $tanggal = $tanggal ?? Carbon::now();
            $kode = 'RCG-PMB-' . $tanggal->format('YmdHis') . '-' . strtoupper(substr(md5(random_int(1, 999999)), 0, 5));
            $deskripsi = "Pengakuan pendapatan PMB (manual) {$student->name}";

            $transaksi = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal->toDateString(),
                'type' => 'pengakuan_pendapatan',
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $akunPBDPMB,
                'parent_akun_id' => $akunPendPMBParent,
                'amount' => $amount,
                'saldo' => 0,
                'bidang_name' => 2,
                'student_id' => $student->id,
            ]);

            // Debit PBD PMB (sebesar amount pengakuan)
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPBDPMB,
                'debit' => $amount,
                'credit' => 0,
            ]);

            // Kredit ke akun-akun terpilih (dimulai dari pilihan user)
            foreach ($allocations as $akunId => $amt) {
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $akunId,
                    'debit' => 0,
                    'credit' => round($amt, 2),
                ]);
            }
        });

        // Build message ringkas
        $lines = [];
        foreach ($allocations as $akunId => $amt) {
            $akun = DB::table('akun_keuangans')->select('kode_akun', 'nama_akun')->where('id', $akunId)->first();
            $label = $akun ? ($akun->kode_akun . ' - ' . $akun->nama_akun) : ('Akun #' . $akunId);
            $lines[] = $label . ' (Rp ' . number_format($amt, 2, ',', '.') . ')';
        }

        return [
            'ok' => true,
            'message' => "Pengakuan pendapatan PMB {$student->name} berhasil sebesar Rp " . number_format($amount, 2, ',', '.') .
                ". Dialokasikan ke: " . implode(', ', $lines),
        ];
    }

    public function recognizePmbPreview(Student $student)
    {
        $payload = RevenueRecognitionService::previewPMBRecognitionManual($student);
        Log::info('PMB Preview', $payload);
        return response()->json($payload);
    }
}
