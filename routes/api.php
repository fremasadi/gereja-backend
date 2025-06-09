<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChurchEventController;
use App\Http\Controllers\CounselingController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\Api\InfaqController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\MarriageController;

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
Route::post('/infaq/callback', [InfaqController::class, 'callback'])->name('infaq.callback');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/church-events', [ChurchEventController::class, 'index']);

    Route::post('/counselings', [CounselingController::class, 'store']);
    Route::get('/seat-bookings/available-seats', [SeatBookingController::class, 'availableSeats']);
    Route::post('/seat-bookings', [SeatBookingController::class, 'bookSeat']);
    Route::get('/my-bookings', [SeatBookingController::class, 'myBookings']);
    Route::get('/communities', [CommunityController::class, 'index']);


    Route::get('/marriages', [MarriageController::class, 'index']);
    Route::get('/marriages/{id}', [MarriageController::class, 'show']);
    Route::post('/marriages', [MarriageController::class, 'store']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/infaq', [InfaqController::class, 'create']);

});


