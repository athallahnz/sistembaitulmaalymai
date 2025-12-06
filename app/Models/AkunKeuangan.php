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
        'icon'
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

    /**
     * Helper: aggregate saldo per akun (mirip buildAktivitasData()).
     * Menghasilkan collection keyed by akun_keuangan_id:
     * [akun_id => (object){ akun_keuangan_id, total_debit, total_credit }]
     *
     * $excludeLawan:
     *   - null  : semua transaksi
     *   - true  : HANYA yang bukan %-LAWAN
     *   - false : HANYA yang %-LAWAN
     */
    public static function buildSaldoPerAkun(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $bidangId = null,
        ?bool $excludeLawan = null
    ): \Illuminate\Support\Collection {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();

        $query = Ledger::select(
            'akun_keuangan_id',
            DB::raw('SUM(debit)  as total_debit'),
            DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate, $bidangId, $excludeLawan) {
                $q->whereDate('tanggal_transaksi', '>=', $startDate)
                    ->whereDate('tanggal_transaksi', '<=', $endDate);

                if (!is_null($bidangId)) {
                    $q->where('bidang_name', $bidangId);
                }

                if ($excludeLawan === true) {
                    $q->where('kode_transaksi', 'not like', '%-LAWAN');
                } elseif ($excludeLawan === false) {
                    $q->where('kode_transaksi', 'like', '%-LAWAN');
                }
            });

        return $query->groupBy('akun_keuangan_id')
            ->get()
            ->keyBy('akun_keuangan_id');
    }

    /**
     * Ambil semua akun dalam suatu grup (berdasarkan parent_id).
     */
    public static function getAkunByGroup(int $groupId)
    {
        return AkunKeuangan::where('parent_id', $groupId)->get();
    }
}
