<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payable_id',
        'payable_type',
        'payment_type',
        'payment_gateway',
        'payment_status',
        'payment_va_name',
        'payment_va_number',
        'payment_qr_url',        // Tambahkan ini
        'payment_deeplink',      // Tambahkan ini
        'gross_amount',
        'payment_gateway_response',
        'payment_gateway_reference_id',
        'transaction_time',
        'expired_at',
        'payment_date',
        'payment_proof',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'transaction_time' => 'datetime',
        'expired_at' => 'datetime',
        'payment_date' => 'datetime',
        'payment_gateway_response' => 'array',
    ];

    public function payable()
    {
        return $this->morphTo();
    }
}
