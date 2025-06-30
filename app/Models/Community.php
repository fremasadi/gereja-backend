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
// app/Models/Community.php
protected $casts = [
    'images' => 'array',
];

// Accessor untuk mengembalikan path lengkap
public function getImagesAttribute($value)
{
    if (!$value) return [];
    
    $images = is_string($value) ? json_decode($value, true) : $value;
    
    return collect($images)->map(function ($image) {
        // Jika sudah ada path communities, kembalikan apa adanya
        if (str_starts_with($image, 'communities/')) {
            return $image;
        }
        // Jika tidak, tambahkan prefix communities/
        return 'communities/' . ltrim($image, '/');
    })->toArray();
}

// Mutator untuk menyimpan tanpa prefix
public function setImagesAttribute($value)
{
    if (!$value) {
        $this->attributes['images'] = json_encode([]);
        return;
    }
    
    $images = collect($value)->map(function ($image) {
        // Hapus prefix communities/ saat menyimpan
        return str_replace('communities/', '', $image);
    })->toArray();
    
    $this->attributes['images'] = json_encode($images);
}

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
