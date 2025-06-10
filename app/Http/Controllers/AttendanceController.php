<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function scanBarcode(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string',
            'tanggal' => 'required|date',
            'checkin_time' => 'required'
        ]);

        // Cari barcode yang sesuai
        $barcode = Barcode::where('name', $data['name'])
            ->where('tanggal', $data['tanggal'])
            ->where('checkin_time', $data['checkin_time'])
            ->first();

        if (!$barcode) {
            return response()->json([
                'status' => false,
                'message' => 'Barcode tidak valid atau tidak ditemukan'
            ], 404);
        }

        // Validasi waktu saat ini
        $now = Carbon::now();
        $checkinAllowedAt = Carbon::parse($data['tanggal'] . ' ' . $data['checkin_time'])->subMinutes(20);

        if ($now->lt($checkinAllowedAt)) {
            return response()->json([
                'status' => false,
                'message' => 'Belum bisa check-in, maksimal 20 menit sebelum waktu checkin'
            ], 403);
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $data['tanggal'])
            ->first();

        if (!$attendance) {
            // Check-in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'tanggal' => $data['tanggal'],
                'checkin_time' => now()->format('H:i:s'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-in berhasil',
                'type' => 'checkin',
                'data' => $attendance
            ]);
        }

        if (!$attendance->checkout_time) {
            $attendance->update([
                'checkout_time' => now()->format('H:i:s')
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-out berhasil',
                'type' => 'checkout',
                'data' => $attendance
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Sudah melakukan check-in dan check-out hari ini',
        ]);
    }
}
