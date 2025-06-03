<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorshipService extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',             // Nama ibadah (contoh: "Ibadah pagi", "Ibadah sore")
        'service_time',     // Waktu ibadah (format: 08:00:00)
        'is_active',        // Status aktif ibadah
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the bookings for the worship service on a specific date.
     */
    public function bookingsForDate($date)
    {
        return SeatBooking::where('worship_service_id', $this->id)
                          ->whereDate('service_date', $date)
                          ->get();
    }
    
    /**
     * Get the number of bookings for this service on a specific date.
     */
    public function getBookingCountForDate($date)
    {
        return SeatBooking::where('worship_service_id', $this->id)
                         ->whereDate('service_date', $date)
                         ->where('status', 'confirmed')
                         ->count();
    }
    
    /**
     * Check if the service is full for a specific date.
     */
    public function isFullForDate($date)
    {
        if (!$this->capacity) {
            return false;
        }
        
        return $this->getBookingCountForDate($date) >= $this->capacity;
    }
    
    /**
     * Get the next Sunday's service date.
     */
    public static function getNextSundayDate()
    {
        $date = now();
        if ($date->dayOfWeek !== 0) { // 0 = Sunday
            $date = $date->next(0); // Get next Sunday
        }
        return $date->format('Y-m-d');
    }
    
    /**
     * Get upcoming Sunday service dates for the next N weeks.
     */
    public static function getUpcomingSundayDates($weeks = 4)
    {
        $dates = [];
        $date = now();
        
        // If today is Sunday, include today
        if ($date->dayOfWeek === 0) {
            $dates[] = $date->format('Y-m-d');
            $weeks--;
        }
        
        // Get next N Sundays
        for ($i = 0; $i < $weeks; $i++) {
            $date = $date->next(0); // Get next Sunday
            $dates[] = $date->format('Y-m-d');
        }
        
        return $dates;
    }
}