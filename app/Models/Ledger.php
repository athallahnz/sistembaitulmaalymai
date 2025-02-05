<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = ['transaksi_id', 'akun_keuangan_id', 'debit', 'credit'];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id','id'); // Relasi dengan tabel transaksi
    }

    public function akun_keuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id'); // Relasi dengan tabel akun_keuangan
    }
}

