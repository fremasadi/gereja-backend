<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'leader_name',
        'contact_phone',
        'images',
        'status',
    ];

    // Konversi otomatis ke array
    protected $casts = [
        'images' => 'array',
    ];

    // Accessor jika ingin nama komunitas capitalized
    public function getFormattedNameAttribute()
    {
        return ucwords(strtolower($this->name));
    }

    // Scope aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
