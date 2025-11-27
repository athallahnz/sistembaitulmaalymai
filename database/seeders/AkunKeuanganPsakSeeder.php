<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunKeuangan;
use Illuminate\Support\Str;

class AkunKeuanganPsakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $akunList = AkunKeuangan::all();

        foreach ($akunList as $akun) {
            $nama = Str::lower($akun->nama_akun ?? '');
            $kode = (string) $akun->kode_akun;
            $tipe = $akun->tipe_akun;

            $kategoriPsak = null;
            $pembatasan = null;
            $isKasBank = false;
            $cashflowCategory = $akun->cashflow_category; // keep jika sudah diisi

            /*
             |===========================================
             | 1) KLASIFIKASI BERDASARKAN TIPE AKUN
             |===========================================
             */

            if ($tipe === 'asset') {

                // --- ASET LANCAR: Kas & Bank (101, 102, 101-*, 102-*) ---
                $isKasAtauBankKode = Str::startsWith($kode, '101') || Str::startsWith($kode, '102');
                $isKasAtauBankNama = Str::startsWith($nama, 'kas') || Str::startsWith($nama, 'bank');

                if ($isKasAtauBankKode || $isKasAtauBankNama) {
                    $kategoriPsak = 'aset_lancar';
                    $isKasBank = true;

                    // default arus kas = operasional, kalau belum di-set
                    if (empty($cashflowCategory)) {
                        $cashflowCategory = 'operasional';
                    }
                }
                // --- ASET LANCAR: Piutang (103, 103-*) ---
                elseif (Str::startsWith($kode, '103')) {
                    $kategoriPsak = 'aset_lancar';
                }
                // --- ASET TIDAK LANCAR: 104, 105, 106, 107 (Tanah, Inventaris, AIP, Akumulasi Penyusutan, dll.) ---
                else {
                    $kategoriPsak = 'aset_tidak_lancar';
                }
            } elseif ($tipe === 'liability') {
                // Saat ini semua hutang & pendapatan belum diterima dianggap liabilitas jangka pendek
                $kategoriPsak = 'liabilitas_jangka_pendek';
            } elseif ($tipe === 'revenue') {
                $kategoriPsak = 'pendapatan';

                // Default: semua pendapatan → tidak terikat
                // (Donasi, SPP, Infaq, dll. Nanti kalau ada akun dana pembangunan/wakaf baru bisa diubah manual.)
                $pembatasan = 'tidak_terikat';
            } elseif ($tipe === 'expense') {
                $kategoriPsak = 'beban';
                // Pembatasan biasanya tidak di-track di level beban → biarkan null
            } elseif ($tipe === 'equity') {
                // Fokus ke 400-1, 400-2, 400-3 (Aset Neto)
                if ($kode === '400-1' || Str::contains($nama, 'tidak terikat')) {
                    $kategoriPsak = 'aset_neto_tidak_terikat';
                    $pembatasan = 'tidak_terikat';
                } elseif ($kode === '400-2' || Str::contains($nama, 'temporer')) {
                    $kategoriPsak = 'aset_neto_terikat_temporer';
                    $pembatasan = 'terikat_temporer';
                } elseif ($kode === '400-3' || Str::contains($nama, 'permanen') || Str::contains($nama, 'wakaf')) {
                    $kategoriPsak = 'aset_neto_terikat_permanen';
                    $pembatasan = 'terikat_permanen';
                }
                // 400 (induk "Aset Neto") bisa dibiarkan null (header)
            }

            /*
             |===========================================
             | 2) SIMPAN HASIL MAPPING
             |===========================================
             */

            $akun->kategori_psak = $kategoriPsak;
            $akun->pembatasan = $pembatasan;
            $akun->is_kas_bank = $isKasBank;
            $akun->cashflow_category = $cashflowCategory;
            $akun->save();
        }
    }
}
