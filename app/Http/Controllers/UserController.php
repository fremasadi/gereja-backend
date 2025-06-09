<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Pastikan semua route di controller ini hanya bisa diakses jika sudah login
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // Ganti ke 'auth:api' jika pakai Passport
    }

    /**
     * Get user profile from auth token.
     */
    public function profile(Request $request)
    {
        $user = Auth::user(); // atau $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Authenticated user profile fetched successfully',
            'user' => $user,
        ]);
    }
}
