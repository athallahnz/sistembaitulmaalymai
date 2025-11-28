<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BukuHarianExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $bidang;
    protected $start;
    protected $end;

    public function __construct($bidang = null, $start = null, $end = null)
    {
        $this->bidang = $bidang;
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        $q = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
            ->excludeInternalKasBankAndLawan()   // ⬅️ penting
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
            'Tanggal',
            'Tahun',
            'Bulan',
            'Kode Transaksi',
            // 'Bidang',
            'Deskripsi',
            // 'Kode Akun',
            'Nama Akun',
            'Akun Induk',
            'Tipe Transaksi',
            'Debit',
            'Kredit',
            'Amount Raw'
        ];
    }

    public function map($t): array
    {
        $tanggal = \Carbon\Carbon::parse($t->tanggal_transaksi);

        $akun = $t->akunKeuangan;
        $parent = $t->parentAkunKeuangan;

        // LOGIKA DEBIT KREDIT BERDASARKAN SALDO NORMAL
        if ($akun->saldo_normal === 'debit') {
            $debit = $t->amount;
            $kredit = 0;
        } else {
            $debit = 0;
            $kredit = $t->amount;
        }

        return [
            $tanggal->format('Y-m-d'),
            $tanggal->year,
            $tanggal->format('F'),
            $t->kode_transaksi,
            // $t->bidang_name,
            $t->deskripsi,
            // $akun->kode_akun,
            $akun->nama_akun,
            $parent->nama_akun ?? '-',
            $t->type,
            $debit,
            $kredit,
            $t->amount,
        ];
    }
}
