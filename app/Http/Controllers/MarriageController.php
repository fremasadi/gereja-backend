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

        // Validasi array untuk images
        'fotocopy_ktp' => 'required|array',
        'fotocopy_ktp.*' => 'array', // Setiap item harus berupa array (object image)
        'fotocopy_kk' => 'required|array',
        'fotocopy_kk.*' => 'array',
        'fotocopy_akte_kelahiran' => 'required|array',
        'fotocopy_akte_kelahiran.*' => 'array',
        'fotocopy_akte_baptis_selam' => 'required|array',
        'fotocopy_akte_baptis_selam.*' => 'array',
        'akte_nikah_orang_tua' => 'required|array',
        'akte_nikah_orang_tua.*' => 'array',
        'fotocopy_n1_n4' => 'required|array',
        'fotocopy_n1_n4.*' => 'array',
        'foto_berdua' => 'required|array',
        'foto_berdua.*' => 'array',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Process dan validate image data
        $processedData = $this->processImageData($request);
        
        // Simpan data ke database
        $marriage = Marriage::create([
            'nama_lengkap_pria' => $request->nama_lengkap_pria,
            'nama_lengkap_wanita' => $request->nama_lengkap_wanita,
            'no_telepon' => $request->no_telepon,
            'tanggal_pernikahan' => $request->tanggal_pernikahan,
            'fotocopy_ktp' => $processedData['fotocopy_ktp'],
            'fotocopy_kk' => $processedData['fotocopy_kk'],
            'fotocopy_akte_kelahiran' => $processedData['fotocopy_akte_kelahiran'],
            'fotocopy_akte_baptis_selam' => $processedData['fotocopy_akte_baptis_selam'],
            'akte_nikah_orang_tua' => $processedData['akte_nikah_orang_tua'],
            'fotocopy_n1_n4' => $processedData['fotocopy_n1_n4'],
            'foto_berdua' => $processedData['foto_berdua'],
        ]);

        // Return response dengan data yang sudah diformat
        $response = $marriage->toArray();
        
        // Format image data for response (tanpa base64 untuk menghemat bandwidth)
        $imageFields = [
            'fotocopy_ktp', 'fotocopy_kk', 'fotocopy_akte_kelahiran',
            'fotocopy_akte_baptis_selam', 'akte_nikah_orang_tua', 
            'fotocopy_n1_n4', 'foto_berdua'
        ];
        
        foreach ($imageFields as $field) {
            if (isset($response[$field]) && is_array($response[$field])) {
                $response[$field] = array_map(function($image) {
                    return [
                        'filename' => $image['filename'] ?? '',
                        'format' => $image['format'] ?? '',
                        'size' => $image['size'] ?? 0,
                        'uploaded_at' => $image['uploaded_at'] ?? '',
                        // Hapus 'data' untuk response (base64 terlalu besar)
                    ];
                }, $response[$field]);
            }
        }

        return response()->json([
            'message' => 'Marriage record created successfully', 
            'data' => $response
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Error creating marriage record: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create marriage record'], 500);
    }
}

private function processImageData(Request $request)
{
    $imageFields = [
        'fotocopy_ktp', 'fotocopy_kk', 'fotocopy_akte_kelahiran',
        'fotocopy_akte_baptis_selam', 'akte_nikah_orang_tua', 
        'fotocopy_n1_n4', 'foto_berdua'
    ];
    
    $processedData = [];
    
    foreach ($imageFields as $field) {
        $images = $request->input($field, []);
        $processedImages = [];
        
        foreach ($images as $image) {
            // Validate image data structure
            if (is_array($image) && isset($image['filename'], $image['format'], $image['data'])) {
                // Optional: Validate base64 data
                if ($this->isValidBase64($image['data'])) {
                    $processedImages[] = [
                        'filename' => sanitize_filename($image['filename']),
                        'format' => strtolower($image['format']),
                        'data' => $image['data'],
                        'size' => $image['size'] ?? strlen($image['data']),
                        'uploaded_at' => $image['uploaded_at'] ?? now()->toISOString(),
                    ];
                } else {
                    \Log::warning("Invalid base64 data for image: " . $image['filename']);
                }
            } else {
                \Log::warning("Invalid image data structure for field: $field");
            }
        }
        
        $processedData[$field] = $processedImages;
    }
    
    return $processedData;
}
private function isValidBase64($data)
{
    // Basic base64 validation
    if (!is_string($data)) return false;
    if (empty($data)) return false;
    
    // Check if it's valid base64
    $decoded = base64_decode($data, true);
    if ($decoded === false) return false;
    
    // Check if it re-encodes to the same string
    return base64_encode($decoded) === $data;
}

}
