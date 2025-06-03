<?php

namespace App\Http\Controllers;

use App\Models\SeatBooking;
use Illuminate\Http\Request;
use App\Models\Seat;
class SeatBookingController extends Controller
{
    /**
     * Get available seats for a given date and worship service.
     */
    public function availableSeats(Request $request)
{
    $request->validate([
        'service_date' => 'required|date',
        'worship_service_id' => 'required|integer|exists:worship_services,id',
    ]);

    // Ambil semua kursi
    $allSeats = Seat::all();

    // Ambil ID kursi yang sudah dibooked pada tanggal & layanan tertentu
    $bookedSeatIds = SeatBooking::where('service_date', $request->service_date)
        ->where('worship_service_id', $request->worship_service_id)
        ->where('status', 'booked')
        ->pluck('seat_id')
        ->toArray();

    // Tandai setiap kursi apakah sudah dibooked
    $seatsWithStatus = $allSeats->map(function ($seat) use ($bookedSeatIds) {
        return [
            'id' => $seat->id,
            'row' => $seat->row,
            'number' => $seat->number,
            'label' => $seat->label,
            'is_booked' => in_array($seat->id, $bookedSeatIds),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $seatsWithStatus,
    ]);
}

public function bookSeat(Request $request)
{
    $request->validate([
        'worship_service_id' => 'required|exists:worship_services,id',
        'seat_id' => 'required|exists:seats,id',
        'service_date' => 'required|date',
    ]);

    // Cek apakah kursi sudah dibooking
    if (SeatBooking::isSeatBooked($request->seat_id, $request->service_date, $request->worship_service_id)) {
        return response()->json([
            'success' => false,
            'message' => 'Seat is already booked for this date and service.',
        ], 409); // 409 Conflict
    }

    $booking = SeatBooking::create([
        'worship_service_id' => $request->worship_service_id,
        'seat_id' => $request->seat_id,
        'user_id' => auth()->id(), // asumsi sudah login
        'service_date' => $request->service_date,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Seat successfully booked.',
        'data' => $booking,
    ]);
}

public function myBookings(Request $request)
{
    $user = $request->user(); // Dapatkan user dari token

    $bookings = SeatBooking::with(['seat', 'worshipService'])
        ->where('user_id', $user->id)
        ->orderBy('service_date', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $bookings,
    ]);
}



}
