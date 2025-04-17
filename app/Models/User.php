<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'nomor',
        'pin',
        'role',
        'bidang_name',
        'last_login_at',
        'last_activity_at',
        'is_active',
        'foto',
    ];
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pin', // Sembunyikan PIN dari output JSON/API
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];
    // public function bidang()
    // {
    //     return $this->belongsTo(Bidang::class, 'bidang_id', 'id');
    // }
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_name');
    }
}
