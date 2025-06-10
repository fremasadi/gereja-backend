<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'checkin_time',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'checkin_time' => 'datetime:H:i',
    ];
}
