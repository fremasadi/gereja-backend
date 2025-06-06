<?php

namespace App\Http\Controllers;

use App\Models\Marriage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MarriageController extends Controller
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_FORMATS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];
    
    public function __construct()
    {
        $this->middleware('throttle:10,1')->only('store');
    }

    public function index()
    {
        $marriages = Marriage::all();
        return response()->json($marriages);
    }

    public function show($id)
    {
        $marriage = Marriage::find($id);

        if (!$marriage) {
            return response()->json(['message' => 'Marriage record not found'], 404);
        }

        return response()->json($marriage);
    }

    public function store(Request $request)
    {
        // Increase memory limit for large uploads
        ini_set('memory_limit', '256M');
        
        $validator = Validator::make($request->all(), [
            'nama_lengkap_pria' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/',
            'nama_lengkap_wanita' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/',
            'no_telepon' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'tanggal_pernikahan' => 'required|date|after_or_equal:today',
            
            // Image fields validation
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

        DB::beginTransaction();
        
        try {
            // Create marriage record first to get ID
            $marriage = Marriage::create([
                'nama_lengkap_pria' => $request->nama_lengkap_pria,
                'nama_lengkap_wanita' => $request->nama_lengkap_wanita,
                'no_telepon' => $request->no_telepon,
                'tanggal_pernikahan' => $request->tanggal_pernikahan,
            ]);

            // Process and save images
            $processedData = $this->processAndSaveImages($request, $marriage->id);
            
            // Update marriage record with image paths
            $marriage->update($processedData);
            
            DB::commit();

            return response()->json([
                'message' => 'Marriage record created successfully', 
                'data' => $marriage->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files on error
            if (isset($marriage)) {
                $this->cleanupUploadedFiles($marriage->id);
            }
            
            \Log::error('Error creating marriage record: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to create marriage record',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function processAndSaveImages(Request $request, $marriageId)
    {
        $imageFields = [
            'fotocopy_ktp', 'fotocopy_kk', 'fotocopy_akte_kelahiran',
            'fotocopy_akte_baptis_selam', 'akte_nikah_orang_tua', 
            'fotocopy_n1_n4', 'foto_berdua'
        ];
        
        $processedData = [];
        
        foreach ($imageFields as $field) {
            $rawImages = $request->input($field);
            
            // Handle JSON string input
            if (is_string($rawImages)) {
                $images = json_decode($rawImages, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Invalid JSON for field $field: " . json_last_error_msg());
                }
            } else {
                $images = $rawImages ?? [];
            }

            if (!is_array($images)) {
                throw new \Exception("Field $field must be an array");
            }

            $processedImages = [];
            
            foreach ($images as $index => $image) {
                if (!$this->validateImageStructure($image)) {
                    throw new \Exception("Invalid image structure for $field at index $index");
                }
                
                // Validate file constraints
                $this->validateImageConstraints($image);
                
                // Save to storage and get file info
                $savedImage = $this->saveImageToStorage($image, $field, $marriageId);
                $processedImages[] = $savedImage;
            }
            
            $processedData[$field] = json_encode($processedImages);
        }
        
        return $processedData;
    }

    private function validateImageStructure($image)
    {
        return is_array($image) && 
               isset($image['filename']) && 
               isset($image['format']) && 
               isset($image['data']);
    }

    private function validateImageConstraints($image)
    {
        // Validate file format
        $format = strtolower($image['format']);
        if (!in_array($format, self::ALLOWED_FORMATS)) {
            throw new \Exception("Invalid file format: {$image['format']}. Allowed: " . implode(', ', self::ALLOWED_FORMATS));
        }

        // Validate base64
        if (!$this->isValidBase64($image['data'])) {
            throw new \Exception("Invalid base64 data for file: {$image['filename']}");
        }

        // Validate file size
        $decodedData = base64_decode($image['data']);
        $fileSize = strlen($decodedData);
        
        if ($fileSize > self::MAX_FILE_SIZE) {
            $maxSizeMB = self::MAX_FILE_SIZE / (1024 * 1024);
            throw new \Exception("File too large: {$image['filename']}. Max size: {$maxSizeMB}MB");
        }

        // Validate actual file type (magic bytes)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_buffer($finfo, $decodedData);
        finfo_close($finfo);

        if (!in_array($actualMimeType, self::ALLOWED_MIMES)) {
            throw new \Exception("File content doesn't match declared format for: {$image['filename']}");
        }

        return true;
    }

    private function saveImageToStorage($imageData, $field, $marriageId)
    {
        $decoded = base64_decode($imageData['data']);
        $sanitizedFilename = $this->sanitizeFilename($imageData['filename']);
        $timestamp = now()->format('YmdHis');
        $filename = "{$marriageId}_{$field}_{$timestamp}_{$sanitizedFilename}";
        $path = "marriages/{$marriageId}/{$filename}";
        
        // Save to storage
        if (!Storage::disk('public')->put($path, $decoded)) {
            throw new \Exception("Failed to save file: {$imageData['filename']}");
        }
        
        return [
            'original_filename' => $imageData['filename'],
            'stored_filename' => $filename,
            'path' => $path,
            'format' => strtolower($imageData['format']),
            'size' => strlen($decoded),
            'uploaded_at' => now()->toISOString(),
            'url' => Storage::disk('public')->url($path)
        ];
    }

    private function cleanupUploadedFiles($marriageId)
    {
        try {
            Storage::disk('public')->deleteDirectory("marriages/{$marriageId}");
        } catch (\Exception $e) {
            \Log::warning("Failed to cleanup files for marriage ID {$marriageId}: " . $e->getMessage());
        }
    }

    private function isValidBase64($data)
    {
        if (!is_string($data) || empty($data)) {
            return false;
        }
        
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return false;
        }
        
        return base64_encode($decoded) === $data;
    }

    private function sanitizeFilename($filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        $filename = preg_replace('/[_\.]{2,}/', '_', $filename);
        $filename = trim($filename, '._');
        
        if (empty($filename)) {
            $filename = 'unnamed_' . time();
        }
        
        if (strlen($filename) > 200) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 195 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }
}