<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan Midtrans Payment Gateway
    | Pastikan sudah menambahkan environment variables di .env
    |
    */

    'client_key' => env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-we_qW_rrMRTjG0dI'),
    
    'server_key' => env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-4o24B-5VJ5otHkzRArqHeI_v'),

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

];