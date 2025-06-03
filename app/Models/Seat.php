<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'row',
        'number',
        'label'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'number' => 'integer',
    ];

    /**
     * Get the seat's label.
     *
     * @return string
     */
    public function getLabelAttribute()
    {
        return $this->attributes['label'] ?? $this->attributes['row'].$this->attributes['number'];
    }
}