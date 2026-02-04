<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AkunKeuangan extends Model
{
    use HasFactory;

    protected $table = 'akun_keuangans';
    protected $primaryKey = 'id';

    /**
     * Sesuai DESCRIBE:
     * - id = bigint unsigned, auto_increment
     * - created_at/updated_at ada
     */
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        // Core
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

        // Dashboard columns (baru)
        'show_on_dashboard',
        'dashboard_scope',
        'dashboard_section',
        'dashboard_calc',
        'dashboard_order',
        'dashboard_title',
        'dashboard_link_route',
        'dashboard_link_param',
        'dashboard_format',
        'dashboard_masked',
        'dashboard_icon',
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'is_kas_bank' => 'boolean',
        'show_on_dashboard' => 'boolean',
        'dashboard_masked' => 'boolean',
        'dashboard_order' => 'integer',
    ];

    /* ============================
        RELASI
    ============================ */

    public function parentAkun()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    // Nama relasi yang lebih standar untuk dipakai di controller/detail
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    // Kompatibilitas: kalau ada kode lama yang masih memanggil childAkun()
    public function childAkun()
    {
        return $this->children();
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'akun_keuangan_id');
    }

    public function transaksiLawan()
    {
        return $this->hasMany(Transaksi::class, 'parent_akun_id');
    }

    public function studentCosts()
    {
        return $this->hasMany(StudentCost::class, 'akun_keuangan_id');
    }

    public function eduClasses()
    {
        return $this->belongsToMany(EduClass::class, 'edu_class_akun_keuangan', 'akun_keuangan_id', 'edu_class_id');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'akun_keuangan_id');
    }

    /* ============================
        HELPER: SALDO
    ============================ */

    /**
     * Aggregate saldo per akun dari ledger dalam periode.
     * Return: Collection keyed by akun_keuangan_id
     * [
     *   akun_id => (object){ akun_keuangan_id, total_debit, total_credit }
     * ]
     */
    public static function buildSaldoPerAkun(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $bidangId = null,
        ?bool $excludeLawan = null
    ): Collection {
        $startDate = $startDate ? $startDate->copy()->startOfDay() : now()->copy()->startOfYear();
        $endDate   = $endDate ? $endDate->copy()->endOfDay() : now()->copy()->endOfDay();

        $query = Ledger::query()
            ->select(
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
            })
            ->groupBy('akun_keuangan_id');

        return $query->get()->keyBy('akun_keuangan_id');
    }

    /**
     * Ambil semua akun dalam suatu grup (berdasarkan parent_id).
     */
    public static function getAkunByGroup(int $groupId): Collection
    {
        return self::query()->where('parent_id', $groupId)->get();
    }
}
