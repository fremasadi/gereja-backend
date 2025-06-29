<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function scanBarcode(Request $request)
{
    $user = Auth::user();

    $validated = $request->validate([
        'worship_service_id' => 'required|integer|exists:worship_services,id',
    ]);

    $now = Carbon::now('Asia/Jakarta');

    // Ambil worship service
    $worshipService = \App\Models\WorshipService::find($validated['worship_service_id']);

    if (!$worshipService || !$worshipService->is_active) {
        return response()->json([
            'status' => false,
            'message' => 'Worship service tidak ditemukan atau tidak aktif.',
        ], 404);
    }

    // Waktu check-in boleh dimulai 20 menit sebelum service_time
    $serviceTime = Carbon::parse($worshipService->service_time, 'Asia/Jakarta');
    $minCheckinTime = $serviceTime->copy()->subMinutes(20);

    if ($now->lt($minCheckinTime)) {
        return response()->json([
            'status' => false,
            'message' => 'Belum memasuki waktu check-in. Silakan tunggu hingga 20 menit sebelum ibadah dimulai.',
        ], 403);
    }

    // Cari apakah user sudah hadir di service ini
    $attendance = Attendance::where('user_id', $user->id)
        ->where('worship_service_id', $worshipService->id)
        ->first();

    if (!$attendance) {
        // Check-in pertama
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'worship_service_id' => $worshipService->id,
            'attendance_date' => $serviceTime->toDateString(),
            'check_in_at' => $now,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Check-in berhasil.',
            'type' => 'checkin',
            'data' => $attendance,
        ]);
    }

    if (is_null($attendance->check_out_at)) {
        $attendance->update([
            'check_out_at' => $now,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Check-out berhasil.',
            'type' => 'checkout',
            'data' => $attendance,
        ]);
    }

    return response()->json([
        'status' => false,
        'message' => 'Anda sudah melakukan check-in dan check-out untuk ibadah ini.',
    ], 400);
}


}
