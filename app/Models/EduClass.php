<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EduClass extends Model
{
    protected $fillable = ['name', 'tahun_ajaran'];

    public function students()
    {
        return $this->hasMany(Student::class, 'edu_class_id');
    }


    public function akunKeuangans()
    {
        return $this->belongsToMany(AkunKeuangan::class, 'edu_class_akun_keuangan', 'edu_class_id', 'akun_keuangan_id');
    }

}
