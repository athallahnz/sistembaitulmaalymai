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
        'kode_transaksi',
        'tanggal_transaksi',
        'type',
        'akun_keuangan_id',
        'parent_akun_id',
        'deskripsi',
        'amount',
        'saldo',
    ];

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'transaksi_id', 'id');
    }
    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function parentAkunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_akun_id', 'id');
    }

    public function akunAsal()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }

    public function akunTujuan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_akun_id');
    }

}
