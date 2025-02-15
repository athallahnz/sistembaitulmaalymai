<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkunKeuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('akun_keuangans')->insert([
            // Aktiva (Debit)
            // ['id' => 101, 'kode_akun' => '101', 'nama_akun' => 'Kas', 'tipe_akun' => 'Asset', 'parent_id' => null, 'saldo_normal' => 'Debit'],
            // ['id' => 102, 'kode_akun' => '102', 'nama_akun' => 'Bank', 'tipe_akun' => 'Asset', 'parent_id' => null, 'saldo_normal' => 'Debit'],
            // ['id' => 103, 'kode_akun' => '103', 'nama_akun' => 'Piutang', 'tipe_akun' => 'Asset', 'parent_id' => null, 'saldo_normal' => 'Debit'],
            // ['id' => 1031, 'kode_akun' => '103-1', 'nama_akun' => 'SPP', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 1032, 'kode_akun' => '103-2', 'nama_akun' => 'Uang Gedung', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 1033, 'kode_akun' => '103-3', 'nama_akun' => 'Uang Kegiatan', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 1034, 'kode_akun' => '103-4', 'nama_akun' => 'Uang Seragam', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 1035, 'kode_akun' => '103-5', 'nama_akun' => 'Kegiatan', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 1036, 'kode_akun' => '103-6', 'nama_akun' => 'Bendahara Umum', 'tipe_akun' => 'Asset', 'parent_id' => 103, 'saldo_normal' => 'Debit'],
            // ['id' => 104, 'kode_akun' => '104', 'nama_akun' => 'Tanah Bangunan', 'tipe_akun' => 'Asset', 'parent_id' => null, 'saldo_normal' => 'Debit'],
            ['id' => 1041, 'kode_akun' => '104-1', 'nama_akun' => 'Masjid', 'tipe_akun' => 'Asset', 'parent_id' => 104, 'saldo_normal' => 'Debit'],
            ['id' => 1042, 'kode_akun' => '104-2', 'nama_akun' => 'Pendidikan', 'tipe_akun' => 'Asset', 'parent_id' => 104, 'saldo_normal' => 'Debit'],
            // ['id' => 105, 'kode_akun' => '105', 'nama_akun' => 'Inventaris', 'tipe_akun' => 'Asset', 'parent_id' => null, 'saldo_normal' => 'Debit'],
            // ['id' => 1051, 'kode_akun' => '105-1', 'nama_akun' => 'Mobil', 'tipe_akun' => 'Asset', 'parent_id' => 105, 'saldo_normal' => 'Debit'],
            // ['id' => 1052, 'kode_akun' => '105-2', 'nama_akun' => 'Inventaris Kantor', 'tipe_akun' => 'Asset', 'parent_id' => 105, 'saldo_normal' => 'Debit'],
            ['id' => 1053, 'kode_akun' => '105-3', 'nama_akun' => 'Alat-alat Kebersihan', 'tipe_akun' => 'Asset', 'parent_id' => 105, 'saldo_normal' => 'Debit'],
            ['id' => 1054, 'kode_akun' => '105-4', 'nama_akun' => 'Ambulance', 'tipe_akun' => 'Asset', 'parent_id' => 105, 'saldo_normal' => 'Debit'],

            // Passiva (Kredit)
            // ['id' => 201, 'kode_akun' => '201', 'nama_akun' => 'Hutang', 'tipe_akun' => 'Liability', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            ['id' => 2011, 'kode_akun' => '201-1', 'nama_akun' => 'Bendahara Umum', 'tipe_akun' => 'Liability', 'parent_id' => 201, 'saldo_normal' => 'Kredit'],
            ['id' => 2012, 'kode_akun' => '201-2', 'nama_akun' => 'Bid. Kemasjidan', 'tipe_akun' => 'Liability', 'parent_id' => 201, 'saldo_normal' => 'Kredit'],
            ['id' => 2013, 'kode_akun' => '201-3', 'nama_akun' => 'Bid. Pendidikan', 'tipe_akun' => 'Liability', 'parent_id' => 201, 'saldo_normal' => 'Kredit'],
            ['id' => 2014, 'kode_akun' => '201-4', 'nama_akun' => 'Bid. Sosial', 'tipe_akun' => 'Liability', 'parent_id' => 201, 'saldo_normal' => 'Kredit'],
            ['id' => 2015, 'kode_akun' => '201-5', 'nama_akun' => 'Bid. Usaha', 'tipe_akun' => 'Liability', 'parent_id' => 201, 'saldo_normal' => 'Kredit'],
            // ['id' => 202, 'kode_akun' => '202', 'nama_akun' => 'Donasi', 'tipe_akun' => 'Liability', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            // ['id' => 2021, 'kode_akun' => '202-1', 'nama_akun' => 'SPP Masuk', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            // ['id' => 2022, 'kode_akun' => '202-2', 'nama_akun' => 'Uang Gedung Masuk', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            // ['id' => 2023, 'kode_akun' => '202-3', 'nama_akun' => 'Uang Kegiatan Masuk', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            // ['id' => 2024, 'kode_akun' => '202-4', 'nama_akun' => 'Uang Seragam Masuk', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            // ['id' => 2025, 'kode_akun' => '202-5', 'nama_akun' => 'Kegiatan Masuk', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            ['id' => 2026, 'kode_akun' => '202-6', 'nama_akun' => 'Infaq Jumat', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            ['id' => 2027, 'kode_akun' => '202-7', 'nama_akun' => 'Wakaf', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],
            ['id' => 2028, 'kode_akun' => '202-8', 'nama_akun' => 'Sumbangan/Donasi', 'tipe_akun' => 'Liability', 'parent_id' => 202, 'saldo_normal' => 'Kredit'],

            // Pengeluaran (Kredit)
            // ['id' => 301, 'kode_akun' => '301', 'nama_akun' => 'Beban Penyusutan', 'tipe_akun' => 'Expense', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            // ['id' => 302, 'kode_akun' => '302', 'nama_akun' => 'Beban Gaji dan Upah', 'tipe_akun' => 'Expense', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            // ['id' => 3021, 'kode_akun' => '302-1', 'nama_akun' => 'Gaji Guru', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            // ['id' => 3022, 'kode_akun' => '302-2', 'nama_akun' => 'Gaji Pegawai', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            // ['id' => 3023, 'kode_akun' => '302-3', 'nama_akun' => 'Gaji Guru Extra', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            // ['id' => 3024, 'kode_akun' => '302-4', 'nama_akun' => 'Gaji Guru TPQ', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            ['id' => 3025, 'kode_akun' => '302-5', 'nama_akun' => 'Gaji Ustadz (Tetap)', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            ['id' => 3026, 'kode_akun' => '302-6', 'nama_akun' => 'Gaji Marbot', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            ['id' => 3027, 'kode_akun' => '302-7', 'nama_akun' => 'Kafalah Asatidz', 'tipe_akun' => 'Expense', 'parent_id' => 302, 'saldo_normal' => 'Kredit'],
            // ['id' => 303, 'kode_akun' => '303', 'nama_akun' => 'Biaya Operasional', 'tipe_akun' => 'Expense', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            // ['id' => 3031, 'kode_akun' => '303-1', 'nama_akun' => 'Listrik', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            // ['id' => 3032, 'kode_akun' => '303-2', 'nama_akun' => 'Telephone', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            // ['id' => 3033, 'kode_akun' => '303-3', 'nama_akun' => 'PDAM', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            // ['id' => 3034, 'kode_akun' => '303-4', 'nama_akun' => 'WiFi', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            // ['id' => 3035, 'kode_akun' => '303-5', 'nama_akun' => 'BPJS', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 3036, 'kode_akun' => '303-6', 'nama_akun' => 'Alat-Alat Kebersihan', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 3037, 'kode_akun' => '303-7', 'nama_akun' => 'Kesekretariatan', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 3038, 'kode_akun' => '303-8', 'nama_akun' => 'Pemeliharaan Inventaris', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 3039, 'kode_akun' => '303-9', 'nama_akun' => 'Pemeliharaan Gedung', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 30310, 'kode_akun' => '303-10', 'nama_akun' => 'Ambulance', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 30311, 'kode_akun' => '303-11', 'nama_akun' => 'Mobil A', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            ['id' => 30312, 'kode_akun' => '303-12', 'nama_akun' => 'Mobil B', 'tipe_akun' => 'Expense', 'parent_id' => 303, 'saldo_normal' => 'Kredit'],
            // ['id' => 304, 'kode_akun' => '304', 'nama_akun' => 'Biaya Kegiatan Siswa', 'tipe_akun' => 'Expense', 'parent_id' => null, 'saldo_normal' => 'Kredit'],
            // ['id' => 3041, 'kode_akun' => '304-1', 'nama_akun' => 'Home Visit', 'tipe_akun' => 'Expense', 'parent_id' => 304, 'saldo_normal' => 'Kredit'],
            // ['id' => 3042, 'kode_akun' => '304-2', 'nama_akun' => 'HUT Murid', 'tipe_akun' => 'Expense', 'parent_id' => 304, 'saldo_normal' => 'Kredit'],
        ]);
    }
}
