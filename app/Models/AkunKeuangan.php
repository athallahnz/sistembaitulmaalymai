<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    // Definisikan relasi hasMany dengan transaksi
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}

