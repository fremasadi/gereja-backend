<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChurchEventController;
use App\Http\Controllers\CounselingController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\Api\InfaqController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);    


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/church-events', [ChurchEventController::class, 'index']);

    Route::post('/counselings', [CounselingController::class, 'store']);
    Route::get('/seat-bookings/available-seats', [SeatBookingController::class, 'availableSeats']);
    Route::post('/seat-bookings', [SeatBookingController::class, 'bookSeat']);
    Route::get('/my-bookings', [SeatBookingController::class, 'myBookings']);

    Route::post('/logout', [AuthController::class, 'logout']);

});


Route::prefix('infaq')->group(function () {
    
    // Routes untuk mobile app (memerlukan autentikasi jika diperlukan)
    Route::middleware(['api'])->group(function () {
        
        // Daftar infaq dengan filter dan pagination
        Route::get('/', [InfaqController::class, 'index']);
        
        // Buat transaksi infaq baru
        Route::post('/', [InfaqController::class, 'store']);
        
        // Detail infaq berdasarkan order_id
        Route::get('/{order_id}', [InfaqController::class, 'show']);
        
        // Cek status transaksi dari Midtrans
        Route::get('/{order_id}/status', [InfaqController::class, 'checkStatus']);
        
        // Statistik infaq
        Route::get('/stats/summary', [InfaqController::class, 'statistics']);
        
    });
    
    // Webhook dari Midtrans (tidak perlu autentikasi)
    Route::post('/webhook', [InfaqController::class, 'webhook']);
    
});