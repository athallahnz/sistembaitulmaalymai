<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaliMurid extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'nik',
        'jenis_kelamin',
        'hubungan',
        'no_hp',
        'email',
        'pendidikan_terakhir',
        'pekerjaan',
        'alamat',
        'foto_ktp',
        'student_id'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

}

