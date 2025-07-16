<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counseling extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'gender',
        'date',
        'age',
        'counseling_topic',
        'type',
        'time', // 👈 tambahkan ini

    ];

    protected $casts = [
        'time' => 'datetime:H:i', // atau 'time' => 'time' jika pakai Laravel >= 10
    ];
    
}
