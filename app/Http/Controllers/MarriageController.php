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
        // Log raw input untuk debugging
        \Log::info('Raw request data:', [
            'fotocopy_ktp_type' => gettype($request->input('fotocopy_ktp')),
            'fotocopy_ktp_raw' => $request->input('fotocopy_ktp'),
        ]);

        $validator = Validator::make($request->all(), [
            'nama_lengkap_pria' => 'required|string|max:255',
            'nama_lengkap_wanita' => 'required|string|max:255',
            'no_telepon' => 'required|string|max:20',
            'tanggal_pernikahan' => 'required|date',

            // Ubah validasi - terima string JSON atau array
            'fotocopy_ktp' => 'required',
            'fotocopy_kk' => 'required',
            'fotocopy_akte_kelahiran' => 'required',
            'fotocopy_akte_baptis_selam' => 'required',
            'akte_nikah_orang_tua' => 'required',
            'fotocopy_n1_n4' => 'required',
            'foto_berdua' => 'required',
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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
            $rawImages = $request->input($field);
            
            // Handle jika data datang sebagai string JSON
            if (is_string($rawImages)) {
                $images = json_decode($rawImages, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::warning("Invalid JSON for field $field: " . json_last_error_msg());
                    $images = [];
                }
            } else {
                $images = $rawImages ?? [];
            }

            // Pastikan $images adalah array
            if (!is_array($images)) {
                \Log::warning("Field $field is not an array after processing");
                $images = [];
            }

            \Log::info("Processing field $field", [
                'type' => gettype($images),
                'count' => is_array($images) ? count($images) : 0,
                'sample' => is_array($images) && !empty($images) ? array_keys($images[0] ?? []) : 'empty'
            ]);
            
            $processedImages = [];
            
            foreach ($images as $index => $image) {
                // Validate image data structure
                if (is_array($image) && isset($image['filename'], $image['format'], $image['data'])) {
                    // Optional: Validate base64 data
                    if ($this->isValidBase64($image['data'])) {
                        $processedImages[] = [
                            'filename' => $this->sanitizeFilename($image['filename']),
                            'format' => strtolower($image['format']),
                            'data' => $image['data'],
                            'size' => $image['size'] ?? strlen($image['data']),
                            'uploaded_at' => $image['uploaded_at'] ?? now()->toISOString(),
                        ];
                        \Log::info("Successfully processed image {$index} for field {$field}");
                    } else {
                        \Log::warning("Invalid base64 data for image: " . ($image['filename'] ?? 'unknown'));
                    }
                } else {
                    \Log::warning("Invalid image data structure for field: $field at index $index", [
                        'received_keys' => is_array($image) ? array_keys($image) : gettype($image),
                        'expected_keys' => ['filename', 'format', 'data']
                    ]);
                }
            }
            
            $processedData[$field] = $processedImages;
            \Log::info("Final processed count for $field: " . count($processedImages));
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

    /**
     * Sanitize filename to remove dangerous characters
     */
    private function sanitizeFilename($filename)
    {
        // Remove path separators and dangerous characters
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        
        // Remove multiple consecutive underscores/dots
        $filename = preg_replace('/[_\.]{2,}/', '_', $filename);
        
        // Remove leading/trailing dots and underscores
        $filename = trim($filename, '._');
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'unnamed_' . time();
        }
        
        // Limit filename length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }
}