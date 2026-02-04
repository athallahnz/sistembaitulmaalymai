<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EduPaymentItem extends Model
{
    protected $fillable = ['edu_payment_id', 'bill_type', 'bill_id', 'amount'];

    public function payment()
    {
        return $this->belongsTo(EduPayment::class, 'edu_payment_id');
    }
}
