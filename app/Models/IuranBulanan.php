<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IuranBulanan extends Model
{
    use HasFactory;

    protected $table = 'iuran_bulanan';

    protected $fillable = [
        'warga_kepala_id',
        'tahun',
        'bulan',
        'nominal_tagihan',
        'nominal_bayar',
        'status',
        'tanggal_bayar',
        'metode_bayar',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'nominal_tagihan' => 'integer',
        'nominal_bayar' => 'integer',
        'tanggal_bayar' => 'datetime',
    ];

    public const STATUS_BELUM = 'belum';
    public const STATUS_SEBAGIAN = 'sebagian';
    public const STATUS_LUNAS = 'lunas';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_BELUM,
            self::STATUS_SEBAGIAN,
            self::STATUS_LUNAS,
        ];
    }

    public function wargaKepala()
    {
        return $this->belongsTo(Warga::class, 'warga_kepala_id');
    }

    public function scopeTahun($query, int $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeBulan($query, int $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    public function getIsLunasAttribute(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }

    public function getNamaBulanAttribute(): string
    {
        // mapping simpel 1–12 → nama Indonesia
        $map = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $map[$this->bulan] ?? (string) $this->bulan;
    }
}
