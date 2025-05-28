<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;

    protected $table = 'bidangs';
    protected $fillable = ['name', 'description'];

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'bidang_name', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'bidang_name', 'id');
    }

    public function hutangs()
    {
        return $this->hasMany(Hutang::class, 'bidang_name');
    }

    public function piutangs()
    {
        return $this->hasMany(Piutang::class, 'bidang_name');
    }
}
