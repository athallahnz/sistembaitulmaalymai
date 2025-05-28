<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Piutang extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'akun_keuangan_id',
        'parent_id',
        'jumlah',
        'tanggal_jatuh_tempo',
        'deskripsi',
        'status',
        'bidang_name'
    ];

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
    public function routeNotificationForDatabase()
    {
        return $this->id;
    }

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_name');
    }
}
