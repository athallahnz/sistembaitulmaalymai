<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BukuKasBankExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            // Sheet 1: Buku Kas
            new BukuKasBankSheetExport($this->bidang, $this->start, $this->end, 101),

            // Sheet 2: Buku Bank
            new BukuKasBankSheetExport($this->bidang, $this->start, $this->end, 102),
        ];
    }
}
