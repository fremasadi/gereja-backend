<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Infaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'donor_name',
        'donor_email',
        'amount',
        'type',
        'message',
        'is_anonymous',
        'status',
        'payment_type',
        'payment_code',
        'midtrans_response',
        'transaction_time',
        'settlement_time'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'midtrans_response' => 'array',
        'transaction_time' => 'datetime',
        'settlement_time' => 'datetime'
    ];

    // Scope untuk status
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSettlement($query)
    {
        return $query->where('status', 'settlement');
    }

    public function scopeSuccess($query)
    {
        return $query->whereIn('status', ['settlement', 'capture']);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['deny', 'cancel', 'expire', 'failure']);
    }

    // Scope untuk tipe infaq
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope untuk tanggal
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', Carbon::now()->year);
    }

    // Accessor untuk format nama donor
    public function getDisplayNameAttribute()
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }

    // Accessor untuk format amount
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    // Method untuk mengecek apakah transaksi berhasil
    public function isSuccess()
    {
        return in_array($this->status, ['settlement', 'capture']);
    }

    // Method untuk mengecek apakah transaksi pending
    public function isPending()
    {
        return $this->status === 'pending';
    }

    // Method untuk mengecek apakah transaksi gagal
    public function isFailed()
    {
        return in_array($this->status, ['deny', 'cancel', 'expire', 'failure']);
    }

    // Static method untuk generate order ID
    public static function generateOrderId()
    {
        $prefix = 'INFAQ';
        $timestamp = time();
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $timestamp . '-' . $random;
    }

    public function payment()
{
    return $this->morphOne(Payment::class, 'payable');
}

}