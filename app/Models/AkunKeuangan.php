<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    use HasFactory;

    protected $table = 'akun_keuangans'; // Pastikan sesuai nama tabel
    protected $primaryKey = 'id'; // Sesuai dengan database
    public $incrementing = false; // Karena ID bukan auto-increment
    protected $keyType = 'string'; // Karena ID berisi angka manual
    public $timestamps = false; // Karena `created_at` dan `updated_at` NULL

    protected $fillable = [
        'id', 'nama_akun', 'tipe_akun', 'kode_akun', 'parent_id', 'saldo_normal'
    ];

    public function parentAkun()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id','id');
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}
