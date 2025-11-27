<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    use HasFactory;

    protected $table = 'akun_keuangans';
    protected $primaryKey = 'id';

    // Karena ID bukan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    // created_at & updated_at boleh NULL
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nama_akun',
        'tipe_akun',
        'kategori_psak',
        'pembatasan',
        'kode_akun',
        'parent_id',
        'saldo_normal',
        'is_kas_bank',
        'cashflow_category',
        'icon',
        'created_at',
        'updated_at',
    ];

    /* ============================
        RELASI
    ============================ */

    // Akun Induk
    public function parentAkun()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id', 'id');
    }

    // Anak Akun
    public function childAkun()
    {
        return $this->hasMany(AkunKeuangan::class, 'parent_id', 'id');
    }

    // Relasi ke transaksis (akun utama)
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'akun_keuangan_id');
    }

    // Relasi ke transaksis di mana ini adalah akun lawannya
    public function transaksiLawan()
    {
        return $this->hasMany(Transaksi::class, 'parent_akun_id');
    }

    /* ============================
        SALDO (OPSIONAL)
    ============================ */
    public function getSaldoAttribute()
    {
        // Idealnya, type = penerimaan/pengeluaran → bukan debit/kredit
        // Jadi fungsi ini sebenarnya TIDAK cocok dengan struktur transaksimu sekarang.

        // PERINGATAN:
        // Jika dipakai, harus diperbaiki untuk membaca debit/kredit dari jurnal.

        return 0; // sementara dimatikan supaya tidak menyesatkan
    }

    /* ============================
        RELASI LAIN
    ============================ */

    public function studentCosts()
    {
        return $this->hasMany(StudentCost::class);
    }

    public function eduClasses()
    {
        return $this->belongsToMany(EduClass::class, 'edu_class_akun_keuangan');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'akun_keuangan_id');
    }
}
