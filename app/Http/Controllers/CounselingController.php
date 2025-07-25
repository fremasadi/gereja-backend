<?php

namespace App\Http\Controllers;

use App\Models\Counseling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounselingController extends Controller
{
    public function store(Request $request)
{
    $user = $request->user(); // Ambil user dari token

    $validated = $request->validate([
        'date' => 'required|date',
        'time' => 'required|date_format:H:i', // Validasi time
        'counseling_topic' => 'required|string|max:255',
        'type' => 'required|in:personal,baptis,relationship,family',
    ]);

    // Gabungkan data dari user + validated input
    $data = array_merge($validated, [
        'name'   => $user->name,
        'phone'  => $user->phone,
        'gender' => $user->gender,
        'age'    => $user->age,
    ]);

    $counseling = Counseling::create($data);

    return response()->json([
        'message' => 'Counseling data created successfully.',
        'data' => $counseling
    ], 201);
}

public function index(Request $request)
{
    $user = $request->user();

    $counselings = Counseling::where('name', $user->name)
        ->where('phone', $user->phone)
        ->orderBy('date', 'desc')
        ->get();

    return response()->json([
        'message' => 'Your counseling records retrieved successfully.',
        'data' => $counselings,
    ], 200);
}

}
