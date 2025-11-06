<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kajian extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'youtube_link',
        'image',
        'start_time',
        'jeniskajian_id',
        'ustadz_id',
    ];

    // Relasi ke JenisKajian
    public function jeniskajian()
    {
        return $this->belongsTo(JenisKajian::class);
    }

    // Relasi ke Ustadz
    public function ustadz()
    {
        return $this->belongsTo(Ustadz::class);
    }
}
