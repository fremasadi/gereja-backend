<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchEvent extends Model
{
    use HasFactory;

    protected $table = 'church_events';

    protected $fillable = [
        'location_name',
        'location_address',
        'location_spesific',
        'theme',
        'date',
        'time',
        'bible_verse',
        'sermon_title',
        'sermon_content',
        'images',
        'created_by',
        'is_recurring',
        'recurring_days',
    ];
    
    protected $casts = [
        'recurring_days' => 'array',
        'is_recurring' => 'boolean',
    ];
    
    /**
     * Relasi ke User (pembuat acara)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
