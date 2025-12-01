<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = "transaksis";

    protected $fillable = [
        'bidang_name',
        'kode_transaksi',
        'tanggal_transaksi',
        'type',
        'akun_keuangan_id',
        'parent_akun_id',
        'deskripsi',
        'amount',
        'saldo',
        'user_id',      // user yang membuat transaksi
        'updated_by',   // user yang terakhir meng-update
    ];

    // =================== SCOPE ===================

    public function scopePrimary($q)
    {
        return $q->where('kode_transaksi', 'not like', '%-LAWAN');
    }

    public function scopeExcludeInternalKasBankAndLawan($query)
    {
        return $query
            // 1) Buang baris kode_transaksi yang mengandung "-LAWAN"
            ->where('kode_transaksi', 'not like', '%-LAWAN%')

            // 2) Buang transaksi internal: akun & parent sama-sama kas/bank
            ->where(function ($q) {
                $q->whereHas('akunKeuangan', function ($qq) {
                    $qq->where('is_kas_bank', 0); // akun utama BUKAN kas/bank
                })
                    ->orWhereDoesntHave('parentAkunKeuangan') // tidak ada akun lawan
                    ->orWhereHas('parentAkunKeuangan', function ($qq) {
                        $qq->where('is_kas_bank', 0); // akun lawan BUKAN kas/bank
                    });
            });
    }

    // =================== RELASI ===================

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'transaksi_id', 'id');
    }

    public function akunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }

    public function parentAkunKeuangan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_akun_id', 'id');
    }

    public function akunAsal()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }

    public function akunTujuan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_akun_id');
    }

    public function akun()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_keuangan_id');
    }

    public function parentAkun()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id', 'id');
    }

    // =================== USER LOG ===================

    /**
     * User yang pertama kali membuat transaksi
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User yang terakhir meng-update transaksi
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
