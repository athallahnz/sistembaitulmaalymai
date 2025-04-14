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
}
