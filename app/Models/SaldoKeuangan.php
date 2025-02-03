<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoKeuangan extends Model
{
    use HasFactory;

    protected $table = 'saldo_keuangan';
    protected $fillable = ['akun_keuangan_id', 'saldo_awal', 'saldo_akhir', 'periode'];

    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }
}

