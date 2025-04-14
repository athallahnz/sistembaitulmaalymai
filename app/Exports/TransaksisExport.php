<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransaksisExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bidangName;

    public function __construct($bidangName)
    {
        $this->bidangName = $bidangName;
    }

    /**
     * Fungsi untuk mengambil data transaksi berdasarkan filter bidang_name
     */
    public function collection()
    {
        $user = auth()->user();

        // Ambil transaksi berdasarkan role dan kode_transaksi
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan')
            ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan

        // Filter berdasarkan role 'Bidang'
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $this->bidangName);
        }

        return $transaksiQuery->get();
    }

    /**
     * Menentukan headings di Excel
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal Transaksi',
            'Kode Transaksi',
            'Akun',
            'Sub Akun',
            'Deskripsi',
            'Jumlah',
            'Saldo',
        ];
    }

    /**
     * Melakukan mapping setiap baris data
     */
    public function map($transaksi): array
    {
        return [
            $transaksi->id,
            $transaksi->tanggal_transaksi,
            $transaksi->kode_transaksi,
            $transaksi->akunKeuangan ? $transaksi->akunKeuangan->nama_akun : 'N/A',
            $transaksi->parentAkunKeuangan ? $transaksi->parentAkunKeuangan->nama_akun : 'N/A',
            $transaksi->deskripsi,
            $transaksi->amount,
            $transaksi->saldo, // Menambahkan saldo dari tabel transaksis
        ];
    }

}

