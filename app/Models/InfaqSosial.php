<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfaqSosial extends Model
{
    protected $table = 'infaq_sosials';
    protected $fillable = [
        'warga_id',
        'januari',
        'februari',
        'maret',
        'april',
        'mei',
        'juni',
        'juli',
        'agustus',
        'september',
        'oktober',
        'november',
        'desember',
        'total'
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }

    public static function monthColumns(): array
    {
        return [
            'januari',
            'februari',
            'maret',
            'april',
            'mei',
            'juni',
            'juli',
            'agustus',
            'september',
            'oktober',
            'november',
            'desember'
        ];
    }
}
