<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCost extends Model
{
    protected $fillable = ['student_id', 'akun_keuangan_id', 'jumlah'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }
}
