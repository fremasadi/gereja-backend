<?php

namespace App\Http\Controllers;

use App\Models\Marriage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarriageController extends Controller
{
    // Get list all marriages
    public function index()
    {
        $marriages = Marriage::all();
        return response()->json($marriages);
    }

    // Get detail marriage by id
    public function show($id)
    {
        $marriage = Marriage::find($id);

        if (!$marriage) {
            return response()->json(['message' => 'Marriage record not found'], 404);
        }

        return response()->json($marriage);
    }

    // Store new marriage data
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap_pria' => 'required|string|max:255',
            'nama_lengkap_wanita' => 'required|string|max:255',
            'no_telepon' => 'required|string|max:20',
            'tanggal_pernikahan' => 'required|date',

            // Semua field json harus berupa array valid
            'fotocopy_ktp' => 'required|array',
            'fotocopy_kk' => 'required|array',
            'fotocopy_akte_kelahiran' => 'required|array',
            'fotocopy_akte_baptis_selam' => 'required|array',
            'akte_nikah_orang_tua' => 'required|array',
            'fotocopy_n1_n4' => 'required|array',
            'foto_berdua' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Simpan data ke database
        $marriage = Marriage::create([
            'nama_lengkap_pria' => $request->nama_lengkap_pria,
            'nama_lengkap_wanita' => $request->nama_lengkap_wanita,
            'no_telepon' => $request->no_telepon,
            'tanggal_pernikahan' => $request->tanggal_pernikahan,
            'fotocopy_ktp' => $request->fotocopy_ktp,
            'fotocopy_kk' => $request->fotocopy_kk,
            'fotocopy_akte_kelahiran' => $request->fotocopy_akte_kelahiran,
            'fotocopy_akte_baptis_selam' => $request->fotocopy_akte_baptis_selam,
            'akte_nikah_orang_tua' => $request->akte_nikah_orang_tua,
            'fotocopy_n1_n4' => $request->fotocopy_n1_n4,
            'foto_berdua' => $request->foto_berdua,
        ]);

        return response()->json(['message' => 'Marriage record created successfully', 'data' => $marriage], 201);
    }
}
