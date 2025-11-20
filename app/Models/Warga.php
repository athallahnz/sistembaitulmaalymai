<?php

namespace App\Models;

use App\Models\IuranBulanan;
use App\Models\InfaqSosial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Warga extends Model
{
    protected $table = 'wargas';

    protected $fillable = [
        'nama',
        'rt',
        'alamat',
        'no',
        'hp',
        'pin',
        'warga_id',
        'infaq_sosial_id',
        'status_keluarga',   // ⬅️ tambahkan ini
    ];

    protected $hidden = ['pin'];

    public function infaq()
    {
        return $this->hasOne(InfaqSosial::class, 'warga_id');
    }

    public function iuranBulanan()
    {
        return $this->hasMany(IuranBulanan::class, 'warga_kepala_id');
    }

    /**
     * Relasi ke kepala keluarga (jika record ini adalah anggota).
     * Dulu namanya kepalaKeluarga(), tapi tabrakan dengan scope.
     */
    public function kepala()
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }

    /**
     * Relasi ke anggota-anggota keluarga (jika record ini adalah kepala).
     */
    public function anggotaKeluarga()
    {
        return $this->hasMany(Warga::class, 'warga_id');
    }

    /**
     * Scope: hanya kepala keluarga (warga_id = NULL).
     * Dipakai di: Warga::kepalaKeluarga()->...
     */
    public function scopeKepalaKeluarga($query)
    {
        return $query->whereNull('warga_id');
    }

    // hash otomatis saat set PIN (jika diisi)
    public function setPinAttribute($value)
    {
        if (is_null($value) || $value === '') {
            $this->attributes['pin'] = null;
        } else {
            // kalau value terlihat sudah hash, biarkan (panjang 60 biasanya bcrypt)
            $this->attributes['pin'] = strlen($value) >= 50 ? $value : Hash::make($value);
        }
    }
}
