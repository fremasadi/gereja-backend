<?php

namespace App\Http\Controllers;

use App\Models\Counseling;
use Illuminate\Http\Request;

class CounselingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'date' => 'required|date',
            'age' => 'required|integer|min:1',
            'counseling_topic' => 'required|string|max:255',
            'type' => 'required|in:personal,baptis,relationship,family',
        ]);

        $counseling = Counseling::create($validated);

        return response()->json([
            'message' => 'Counseling data created successfully.',
            'data' => $counseling
        ], 201);
    }
}
