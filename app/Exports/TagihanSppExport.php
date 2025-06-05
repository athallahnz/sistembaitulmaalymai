<?php
namespace App\Exports;

use App\Models\TagihanSpp;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TagihanSppExport implements FromCollection, WithHeadings
{
    protected $tahun, $bulan, $edu_class_ids;

    public function __construct($tahun, $bulan, $edu_class_ids = [])
    {
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->edu_class_ids = $edu_class_ids;
    }

    public function collection()
    {
        $query = TagihanSpp::with(['student.eduClass', 'student.waliMurid'])
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan);

        if (!empty($this->edu_class_ids)) {
            // Filter tagihan yang kelasnya ada di list kelas yang dipilih
            $query->whereHas('student.eduClass', function ($q) {
                $q->whereIn('id', $this->edu_class_ids);
            });
        }

        $tagihans = $query->get();

        return $tagihans->map(function ($item) {
            $s = $item->student;
            $wali = $s->waliMurid;
            $className = $s->eduClass->name ?? '';
            if (Str::startsWith($className, 'KB')) {
                $jenjang = 'KB';
            } elseif (Str::startsWith($className, 'TK')) {
                $jenjang = 'TK';
            } else {
                $jenjang = 'Lain'; // fallback jika tidak sesuai
            }
            return [
                'Nomor Tagihan' => "INV/{$item->tahun}/{$item->bulan}/{$jenjang}-{$s->no_induk}",
                'Nomor Pembayaran' => $s->no_induk,
                'Nama' => $s->name,
                'Email' => $wali->email ?? '',
                'Telepon' => $wali->no_hp ?? '',
                'Tanggal Aktif' => \Carbon\Carbon::parse($item->tanggal_aktif)->format('n/j/Y h:i:s A'),
                'Tanggal Berakhir' => null,
                'Urutan' => 1,
                'Jenis Tagihan' => 1,
                'Attribute 1' => $s->no_induk,
                'Attribute 2' => $s->eduClass->name ?? '',
                'Attribute 3' => null,
                'Attribute 4' => null,
                'Attribute 5' => null,
                'Total Nominal' => $item->jumlah,
                'Rincian 1' => "SPP " . bulanIndo($item->bulan) . " {$item->tahun}",
                'Nominal 1' => $item->jumlah,
                'Rincian 2' => null,
                'Nominal 2' => null,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nomor Tagihan',
            'Nomor Pembayaran',
            'Nama',
            'Email',
            'Telepon',
            'Tanggal Aktif',
            'Tanggal Berakhir',
            'Urutan',
            'Jenis Tagihan',
            'Attribute 1',
            'Attribute 2',
            'Attribute 3',
            'Attribute 4',
            'Attribute 5',
            'Total Nominal',
            'Rincian 1',
            'Nominal 1',
            'Rincian 2',
            'Nominal 2'
        ];
    }
}
