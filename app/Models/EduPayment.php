<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EduPayment extends Model {
    protected $fillable = ['student_id', 'jumlah', 'tanggal'];

    public function student() {
        return $this->belongsTo(Student::class);
    }
}
