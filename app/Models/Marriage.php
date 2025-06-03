<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marriage extends Model
{
    use HasFactory;

    protected $table = 'marriages';

    protected $fillable = [
        'nama_lengkap_pria',
        'nama_lengkap_wanita',
        'no_telepon',
        'tanggal_pernikahan',
        'fotocopy_ktp',
        'fotocopy_kk',
        'fotocopy_akte_kelahiran',
        'fotocopy_akte_baptis_selam',
        'akte_nikah_orang_tua',
        'fotocopy_n1_n4',
        'foto_berdua',
    ];

    protected $casts = [
        'fotocopy_ktp' => 'array',
        'fotocopy_kk' => 'array',
        'fotocopy_akte_kelahiran' => 'array',
        'fotocopy_akte_baptis_selam' => 'array',
        'akte_nikah_orang_tua' => 'array',
        'fotocopy_n1_n4' => 'array',
        'foto_berdua' => 'array',
        'tanggal_pernikahan' => 'date',
    ];
}
