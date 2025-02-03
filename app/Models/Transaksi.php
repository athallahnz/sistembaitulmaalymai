<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;
    protected $table = "transaksis";
    protected $fillable = [
        'bidang_name',
        'deskripsi',
        'kode_transaksi',
        'tanggal_transaksi',
        'akun_keuangan_id',
        'parent_akun_id',
        'debit',
        'kredit',
        'saldo',
    ];

    // Definisikan relasi belongsTo dengan akun_keuangan
    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }
    // app/Models/Transaksi.php
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function parentAkunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_akun_id', 'id');
    }

}
