<?php

if (!function_exists('isKasAkun')) {
    /**
     * Menentukan apakah akun keuangan adalah akun kas atau setara kas.
     *
     * Akun kas memiliki tipe 'asset' dan saldo normal 'debit'.
     *
     * @param  object|null  $akun
     * @return bool
     */
    function isKasAkun($akun)
    {
        return $akun && $akun->tipe_akun === 'asset' && $akun->saldo_normal === 'debit';
    }
    function bulanIndo($bulan)
    {
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
        return $bulanList[$bulan] ?? 'Unknown';
    }

}
