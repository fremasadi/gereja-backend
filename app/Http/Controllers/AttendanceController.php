<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Barcode;
use Illuminate\Support\Facades\Auth;
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

        // Validasi barcode
        $barcode = Barcode::where('name', $validated['name'])
            ->where('tanggal', $validated['tanggal'])
            ->where('checkin_time', $validated['checkin_time'])
            ->first();

        if (!$barcode) {
            return response()->json([
                'status' => false,
                'message' => 'Barcode tidak valid',
            ], 404);
        }

        // Validasi waktu boleh scan
        $allowedCheckinTime = Carbon::parse("{$validated['tanggal']} {$validated['checkin_time']}")
            ->subMinutes(20);

        if (now()->lt($allowedCheckinTime)) {
            return response()->json([
                'status' => false,
                'message' => 'Belum boleh melakukan check-in. Maksimal 20 menit sebelum waktu check-in.'
            ], 403);
        }

        // Cek apakah sudah ada absensi
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $validated['tanggal'])
            ->first();

        if (!$attendance) {
            // Belum ada → lakukan check-in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'attendance_date' => $validated['tanggal'],
                'check_in_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-in berhasil',
                'type' => 'checkin',
                'data' => $attendance
            ]);
        }

        if (is_null($attendance->check_out_at)) {
            // Sudah check-in, belum check-out → lakukan check-out
            $attendance->update([
                'check_out_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-out berhasil',
                'type' => 'checkout',
                'data' => $attendance
            ]);
        }

        // Sudah check-in dan check-out
        return response()->json([
            'status' => false,
            'message' => 'Anda sudah melakukan check-in dan check-out untuk tanggal ini.'
        ], 400);
    }
}
