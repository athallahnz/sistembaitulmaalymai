<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanSpp extends Model
{
    protected $fillable = [
        'student_id',
        'bulan',
        'tahun',
        'jumlah',
        'status',
        'tanggal_aktif',
        'transaksi_id'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
