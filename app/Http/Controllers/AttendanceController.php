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

        // Validasi barcode JSON
        $validated = $request->validate([
            'name' => 'required|string',
            'tanggal' => 'required|date',
            'checkin_time' => 'required|date_format:H:i',
        ]);

        // Validasi name barcode (contoh hanya 'bethany' yang valid)
        if (strtolower($validated['name']) !== 'bethany') {
            return response()->json([
                'status' => false,
                'message' => 'Barcode tidak valid: nama tidak dikenal.',
            ], 403);
        }

        // Waktu barcode untuk validasi
        $barcodeDate = Carbon::parse($validated['tanggal']);
        $barcodeCheckinTime = Carbon::createFromFormat('Y-m-d H:i', $validated['tanggal'] . ' ' . $validated['checkin_time']);
        $minCheckinTime = $barcodeCheckinTime->copy()->subMinutes(20);

        // Cek apakah sekarang sudah dalam jendela waktu check-in
        if (now()->lt($minCheckinTime)) {
            return response()->json([
                'status' => false,
                'message' => 'Belum memasuki waktu check-in. Silakan tunggu hingga 20 menit sebelum check-in.',
            ], 403);
        }

        // Cek apakah absensi sudah ada untuk user & tanggal tersebut
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $barcodeDate)
            ->first();

        if (!$attendance) {
            // Pertama kali → Check-in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'attendance_date' => $barcodeDate->toDateString(),
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
            // Sudah check-in, belum checkout
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

        return response()->json([
            'status' => false,
            'message' => 'Anda sudah melakukan check-in dan check-out hari ini.',
        ], 400);
    }
}
