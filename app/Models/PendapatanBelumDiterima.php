<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendapatanBelumDiterima extends Model
{
    use HasFactory;

    protected $table = 'pendapatan_belum_diterima';

    protected $fillable = [
        'user_id',
        'jumlah',
        'tanggal_pencatatan',
        'deskripsi',
        'bidang_name'
    ];  

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
