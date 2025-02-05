<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    protected $fillable = ['nama_akun', 'kode_akun', 'tipe_akun', 'parent_id', 'saldo_normal'];
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}

