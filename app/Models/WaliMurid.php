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
        'alamat',
        'foto_ktp',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

}

