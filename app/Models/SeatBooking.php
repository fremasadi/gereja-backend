<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatBooking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'worship_service_id', // ID layanan ibadah
        'seat_id',            // ID kursi
        'user_id',            // ID pengguna yang memesan
        'service_date',       // Tanggal ibadah (selalu hari Minggu)
        'status',             // Status pemesanan: 'pending', 'confirmed', 'cancelled'
        'notes',              // Catatan tambahan (opsional)
        'check_in_time',      // Waktu check-in (ketika jemaat hadir)
        'booking_code',       // Kode booking unik
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'service_date' => 'date',
        'check_in_time' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
{
    parent::boot();

    static::creating(function ($booking) {
        // Generate unique booking code jika belum ada
        if (empty($booking->booking_code)) {
            $booking->booking_code = self::generateBookingCode();
        }

        // Set status default ke 'booked' jika belum ditentukan
        if (empty($booking->status)) {
            $booking->status = 'booked';
        }
    });
}


    /**
     * Generate a unique booking code.
     */
    public static function generateBookingCode()
    {
        $prefix = 'GRJ';
        $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Ensure code is unique
        while (self::where('booking_code', $code)->exists()) {
            $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        
        return $code;
    }

    /**
     * Get the seat associated with the booking.
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    /**
     * Get the user who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the worship service for this booking.
     */
    public function worshipService(): BelongsTo
    {
        return $this->belongsTo(WorshipService::class);
    }
    
    /**
     * Scope a query to only include bookings for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('service_date', $date);
    }
    
    /**
     * Scope a query to only include active bookings.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'booked');
    }
    
    /**
     * Check if the booking is active.
     */
    public function isActive()
    {
        return $this->status === 'booked';
    }
    
    /**
     * Check if the booking is checked in.
     */
    public function isCheckedIn()
    {
        return !is_null($this->check_in_time);
    }
    
    /**
     * Check if a seat is already booked for a specific date and service.
     */
    public static function isSeatBooked($seatId, $serviceDate, $worshipServiceId)
    {
        return self::where('seat_id', $seatId)
                  ->where('service_date', $serviceDate)
                  ->where('worship_service_id', $worshipServiceId)
                  ->where('status', 'booked')
                  ->exists();
    }
    
    /**
     * Get available seats for a specific date and service.
     */
    public static function getAvailableSeats($serviceDate, $worshipServiceId)
    {
        $bookedSeatIds = self::where('service_date', $serviceDate)
                            ->where('worship_service_id', $worshipServiceId)
                            ->where('status', 'booked')
                            ->pluck('seat_id')
                            ->toArray();
        
        return Seat::whereNotIn('id', $bookedSeatIds)->get();
    }
}