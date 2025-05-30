<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model {
    protected $fillable = ['name', 'kelas', 'total_biaya', 'rfid_uid'];

    public function payments() {
        return $this->hasMany(EduPayment::class);
    }
}

