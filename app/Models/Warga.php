<?php

// app/Models/Warga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Warga extends Model
{
    protected $table = 'wargas';

    protected $fillable = ['nama', 'rt', 'alamat', 'no', 'hp', 'pin'];

    // jangan pernah expose pin ke JSON
    protected $hidden = ['pin'];

    public function infaq()
    {
        return $this->hasOne(InfaqSosial::class, 'warga_id');
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

