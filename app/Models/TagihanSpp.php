<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanSpp extends Model
{
    protected $fillable = [
        'student_id', 'tahun', 'bulan', 'jumlah', 'status', 'tanggal_aktif'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
