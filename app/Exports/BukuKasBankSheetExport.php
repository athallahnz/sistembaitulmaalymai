<?php

namespace App\Exports;

use App\Models\Transaksi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BukuKasBankSheetExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected $bidang;
    protected $start;
    protected $end;
    protected $groupParentId; // 101 = Kas, 102 = Bank

    protected float $runningSaldo = 0;

    public function __construct($bidang = null, $start = null, $end = null, $groupParentId = 101)
    {
        $this->bidang = $bidang;
        $this->start = $start;
        $this->end = $end;
        $this->groupParentId = $groupParentId;
    }

    public function title(): string
    {
        return $this->groupParentId == 101 ? 'Buku Kas' : 'Buku Bank';
    }

    public function collection()
    {
        // ===========================
        // 1) Tentukan kas/bank akun bidang ini
        // ===========================
        $bidang = $this->bidang;   // integer, dari controller

        // mapping kas per bidang
        $kasMap = [
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ];

        // mapping bank per bidang
        $bankMap = [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        // default null
        $allowedAkunIds = [];

        if ($bidang) {
            // bidang biasa
            $kas = $kasMap[$bidang] ?? null;
            $bank = $bankMap[$bidang] ?? null;
        } else {
            // Bendahara
            $kas = 1011;
            $bank = 1021;
        }

        // hanya ambil akun yang relevan dengan sheet (kas/bank)
        if ($this->groupParentId == 101 && $kas) {
            $allowedAkunIds = [$kas]; // sheet buku kas
        }

        if ($this->groupParentId == 102 && $bank) {
            $allowedAkunIds = [$bank]; // sheet buku bank
        }

        // ===========================
        // 2) Query data transaksi
        // ===========================
        $q = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
            ->whereIn('akun_keuangan_id', $allowedAkunIds)
            ->orderBy('tanggal_transaksi')
            ->orderBy('id');

        if ($this->bidang) {
            $q->where('bidang_name', $this->bidang);
        }

        if ($this->start && $this->end) {
            $q->whereBetween('tanggal_transaksi', [$this->start, $this->end]);
        }

        return $q->get();
    }

    public function headings(): array
    {
        return [
            'TANGGAL',
            'AKUN ID',
            'NAMA AKUN',
            'PARENT AKUN',
            'NAMA AKUN.1',
            'KETERANGAN',
            'PEMASUKAN',
            'PENGELUARAN',
            'SALDO',
        ];
    }

    public function map($t): array
    {
        $tanggal = Carbon::parse($t->tanggal_transaksi);

        $akun = $t->akunKeuangan;
        $parent = $t->parentAkunKeuangan;

        // ==========================
        // PEMASUKAN / PENGELUARAN
        // ==========================
        // Di buku kas/bank, lebih natural pakai kolom type:
        // - type = 'penerimaan'  -> pemasukan
        // - type = 'pengeluaran' -> pengeluaran
        $pemasukan = 0.0;
        $pengeluaran = 0.0;

        if ($t->type === 'penerimaan') {
            $pemasukan = (float) $t->amount;
        } elseif ($t->type === 'pengeluaran') {
            $pengeluaran = (float) $t->amount;
        }

        // ==========================
        // SALDO BERJALAN PER SHEET
        // ==========================
        $this->runningSaldo += ($pemasukan - $pengeluaran);

        return [
            // TANGGAL → format seperti di sheet Excel
            $tanggal->translatedFormat('j F Y'),

            // AKUN ID
            $akun->kode_akun ?? $akun->id ?? null,

            // NAMA AKUN
            $akun->nama_akun ?? '-',

            // PARENT AKUN → kode/ID parent
            $parent
            ? ($parent->kode_akun ?? $parent->id)
            : null,

            // NAMA AKUN.1 → nama parent
            $parent->nama_akun ?? '-',

            // KETERANGAN
            $t->deskripsi,

            // PEMASUKAN
            $pemasukan,

            // PENGELUARAN
            $pengeluaran,

            // SALDO
            $this->runningSaldo,
        ];
    }
}
