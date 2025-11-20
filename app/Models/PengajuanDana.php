<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanDana extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Jika Anda menggunakan $fillable, tambahkan 'alasan_tolak'
    protected $fillable = [
        'user_id',
        'bidang_id',
        'judul',
        'deskripsi',
        'total_jumlah',
        'status',
        'validator_id',
        'tgl_verifikasi',
        'treasurer_id',
        'tgl_pencairan',
        'alasan_tolak',
    ];

    // Relasi ke Detail Item
    public function details()
    {
        return $this->hasMany(PengajuanDanaDetail::class, 'pengajuan_dana_id');
    }

    // Relasi ke User Pembuat
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Bidang
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_id', 'id');
    }

    // Relasi ke Validator (User)
    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    // Relasi ke Treasurer (User)
    public function treasurer()
    {
        return $this->belongsTo(User::class, 'treasurer_id');
    }

    // HELPER CONVERT NUMBER TO ROMAN
    public static function numberToRoman(int $number): string
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];

        // Pastikan angka berada dalam range yang valid
        return $map[$number] ?? 'N/A';
    }
}
