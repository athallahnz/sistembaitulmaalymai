<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfaqKemasjidan extends Model
{
    protected $table = 'infaq_kemasjidans';

    protected $fillable = [
        'tanggal',
        'tahun',
        'bulan',
        'nominal',
        'metode_bayar',
        'sumber',
        'nama_donatur',
        'no_hp',
        'warga_id',
        'keterangan',
        'akun_debit_id',
        'akun_kredit_id',
        'kode_transaksi',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
        'bulan' => 'integer',
        'nominal' => 'decimal:2',
        'akun_debit_id' => 'integer',
        'akun_kredit_id' => 'integer',
        'created_by' => 'integer',
        'warga_id' => 'integer',
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }
}
