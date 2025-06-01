<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['name', 'edu_class_id', 'total_biaya', 'rfid_uid'];

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

}

