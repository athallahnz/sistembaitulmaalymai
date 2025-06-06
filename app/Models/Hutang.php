<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hutang extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'akun_keuangan_id', 'parent_id', 'jumlah', 'tanggal_jatuh_tempo', 'deskripsi', 'status','bidang_name'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class);
    }

    public function parentAkunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id', 'id');
    }

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_name');
    }
}
