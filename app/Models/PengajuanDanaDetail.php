<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanDanaDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi balik ke Header
    public function pengajuan()
    {
        return $this->belongsTo(PengajuanDana::class, 'pengajuan_dana_id');
    }

    // Relasi ke CoA (Akun Keuangan)
    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }
}
