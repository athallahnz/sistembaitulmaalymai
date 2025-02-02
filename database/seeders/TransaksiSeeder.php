<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transaksis')->insert([
            [
                'kode_transaksi' => 'TRX-001',
                'tanggal_transaksi' => '2025-02-01',
                'deskripsi' => 'Pembayaran SPP',
                'akun_keuangan_id' => 1031, // Piutang - SPP
                'debit' => 1000000,
                'kredit' => 0,
                'saldo' => 1000000,
            ],
            [
                'kode_transaksi' => 'TRX-002',
                'tanggal_transaksi' => '2025-02-02',
                'deskripsi' => 'Penerimaan Donasi',
                'akun_keuangan_id' => 202, // Donasi
                'debit' => 0,
                'kredit' => 5000000,
                'saldo' => 5000000,
            ],
            [
                'kode_transaksi' => 'TRX-003',
                'tanggal_transaksi' => '2025-02-03',
                'deskripsi' => 'Pembelian Inventaris Kantor',
                'akun_keuangan_id' => 1052, // Inventaris - Kantor
                'debit' => 2000000,
                'kredit' => 0,
                'saldo' => 2000000,
            ],
            [
                'kode_transaksi' => 'TRX-004',
                'tanggal_transaksi' => '2025-02-04',
                'deskripsi' => 'Pembayaran Gaji Guru',
                'akun_keuangan_id' => 3021, // Beban Gaji - Guru
                'debit' => 0,
                'kredit' => 3000000,
                'saldo' => 3000000,
            ],
            [
                'kode_transaksi' => 'TRX-005',
                'tanggal_transaksi' => '2025-02-05',
                'deskripsi' => 'Pembayaran Listrik',
                'akun_keuangan_id' => 3031, // Biaya Operasional - Listrik
                'debit' => 0,
                'kredit' => 500000,
                'saldo' => 500000,
            ],
        ]);
    }
}
