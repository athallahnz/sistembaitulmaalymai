<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hutang extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'akun_keuangan_id', 'parent_id', 'jumlah', 'tanggal_jatuh_tempo', 'deskripsi', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class);
    }

    public function parentAkunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id', 'id');
    }
}
