<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;

    protected $table = 'bidangs'; // Pastikan sesuai dengan nama tabel di database
    protected $fillable = ['name', 'description'];

    // Relasi dengan Transaksi (Satu bidang bisa memiliki banyak transaksi)
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'bidang_name', 'id');
    }

    // Relasi dengan User (Satu bidang bisa memiliki banyak user)
    public function users()
    {
        return $this->hasMany(User::class, 'bidang_name', 'id');
    }
}
