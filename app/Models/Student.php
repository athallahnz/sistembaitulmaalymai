<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name',
        'jenis_kelamin',
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
    public function eduClass()
    {
        return $this->belongsTo(EduClass::class);
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
        return $this->belongsTo(WaliMurid::class);
    }


}

