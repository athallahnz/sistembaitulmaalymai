<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ustadz extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function kajians()
    {
        return $this->hasMany(Kajian::class);
    }
}
