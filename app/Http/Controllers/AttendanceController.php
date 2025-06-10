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
            'name' => 'required|string',
            'tanggal' => 'required|date',
            'checkin_time' => 'required'
        ]);

        // Validasi hardcoded nama barcode yang diizinkan
        if (strtolower($validated['name']) !== 'bethany') {
            return response()->json([
                'status' => false,
                'message' => 'Barcode tidak valid.',
            ], 403);
        }

        // Hitung waktu awal boleh check-in: 20 menit sebelum waktu checkin
        $earliestCheckInTime = Carbon::parse("{$validated['tanggal']} {$validated['checkin_time']}")->subMinutes(20);

        if (now()->lt($earliestCheckInTime)) {
            return response()->json([
                'status' => false,
                'message' => 'Belum boleh melakukan absensi. Tunggu hingga 20 menit sebelum waktu check-in.',
            ], 403);
        }

        // Cek apakah absensi sudah ada
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $validated['tanggal'])
            ->first();

        if (!$attendance) {
            // Belum ada, lakukan check-in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'attendance_date' => $validated['tanggal'],
                'check_in_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-in berhasil.',
                'type' => 'checkin',
                'data' => $attendance,
            ]);
        }

        if (is_null($attendance->check_out_at)) {
            // Sudah check-in, sekarang checkout
            $attendance->update([
                'check_out_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-out berhasil.',
                'type' => 'checkout',
                'data' => $attendance,
            ]);
        }

        // Sudah check-in dan check-out
        return response()->json([
            'status' => false,
            'message' => 'Anda sudah check-in dan check-out hari ini.',
        ], 400);
    }
}
