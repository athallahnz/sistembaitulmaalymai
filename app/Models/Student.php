<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Student extends Model
{
    protected $fillable = [
        'nisn',
        'no_induk',
        'name',
        'nickname',
        'jenis_kelamin',
        'tempat_lahir',
        'ttl',
        'usia',
        'nik',
        'no_akte',
        'no_kk',
        'alamat_kk',
        'alamat_tinggal',
        'pas_photo',
        'akte',
        'kk',
        'wali_murid_id',
        'edu_class_id',
        'rfid_uid',
        'total_biaya',
    ];


    public function payments()
    {
        return $this->hasMany(EduPayment::class);
    }
    public function biaya()
    {
        return $this->hasMany(StudentCost::class);
    }

    public function eduClass()
    {
        return $this->belongsTo(EduClass::class, 'edu_class_id');
    }

    public function edu_class()
    {
        return $this->belongsTo(EduClass::class);
    }

    public function costs()
    {
        return $this->hasMany(StudentCost::class);
    }

    public function waliMurid()
    {
        return $this->hasOne(WaliMurid::class);
    }

    public function waliMurids()
    {
        return $this->hasMany(WaliMurid::class);
    }

    public function getTtlFormattedAttribute()
    {
        return Carbon::parse($this->ttl)->format('d/m/Y');
    }

    public function getUsiaAttribute()
    {
        $tanggalLahir = Carbon::parse($this->ttl);
        $sekarang = Carbon::now();

        $selisih = $tanggalLahir->diff($sekarang);

        return $selisih->y . ' tahun, ' . $selisih->m . ' bulan, ' . $selisih->d . ' hari';
    }
    public function tagihanSpps()
    {
        return $this->hasMany(TagihanSpp::class, 'student_id');
    }
}

