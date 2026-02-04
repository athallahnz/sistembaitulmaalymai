<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EduPayment extends Model
{
    protected $fillable = [
        'student_id',
        'jumlah',
        'total',
        'tanggal',
        'metode',
        'akun_kas_bank_id',
        'user_id',
        'transaksi_id',
        'verifikasi_token',
        'status_verifikasi',
        'catatan'
    ];

    public function items()
    {
        return $this->hasMany(EduPaymentItem::class, 'edu_payment_id');
    }
}
