<?php

namespace App\Http\Controllers;

use App\Models\Marriage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MarriageController extends Controller
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_FORMATS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];
    private const MAX_IMAGES_PER_FIELD = 5; // Batasi jumlah gambar per field
    
    public function __construct()
    {
        $this->middleware('throttle:10,1')->only('store');
    }

    public function index()
{
    $marriages = Marriage::all()->map(function ($marriage) {
        $marriage->images = collect($marriage->images)->map(function ($filename) use ($marriage) {
            // Contoh: "14_fotocopy_ktp_0_20250607052821_6843cdf58e2fb_scaled_1000000033.jpg"
            $parts = explode('_', $filename);
            $id = $parts[0] ?? 'unknown';
            $folder = $parts[1] ?? 'unknown';

            return "/marriages/{$id}/{$folder}/{$filename}";
        });
        return $marriage;
    });

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
        ini_set('memory_limit', '512M'); // Tingkatkan dari 256M
        set_time_limit(300); // 5 menit timeout
        
        try {
            // Validasi dasar dulu
            $this->validateBasicData($request);
            
            // Validasi struktur image fields
            $this->validateImageFields($request);
            
            DB::beginTransaction();
            
            // Create marriage record first to get ID
            $marriage = Marriage::create([
                'nama_lengkap_pria' => trim($request->nama_lengkap_pria),
                'nama_lengkap_wanita' => trim($request->nama_lengkap_wanita),
                'no_telepon' => $request->no_telepon,
                'tanggal_pernikahan' => $request->tanggal_pernikahan,
                'status' => 'pending', // Tambahkan status
            ]);

            // Process and save images
            $processedData = $this->processAndSaveImages($request, $marriage->id);
            
            // Update marriage record with image paths
            $marriage->update($processedData);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marriage record created successfully', 
                'data' => $marriage->fresh()
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files on error
            if (isset($marriage)) {
                $this->cleanupUploadedFiles($marriage->id);
            }
            
            Log::error('Error creating marriage record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['fotocopy_ktp', 'fotocopy_kk', 'fotocopy_akte_kelahiran', 'fotocopy_akte_baptis_selam', 'akte_nikah_orang_tua', 'fotocopy_n1_n4', 'foto_berdua'])
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create marriage record',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function validateBasicData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap_pria' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/|min:2',
            'nama_lengkap_wanita' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/|min:2',
            'no_telepon' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/|min:10',
            'tanggal_pernikahan' => 'required|date|after_or_equal:today|before:' . now()->addYears(2)->toDateString(),
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function validateImageFields(Request $request)
    {
        $imageFields = $this->getImageFields();
        $errors = [];

        foreach ($imageFields as $field) {
            if (!$request->has($field)) {
                $errors[$field] = ["Field {$field} is required"];
                continue;
            }

            $rawImages = $request->input($field);
            
            // Handle JSON string input
            if (is_string($rawImages)) {
                $images = json_decode($rawImages, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[$field] = ["Invalid JSON format: " . json_last_error_msg()];
                    continue;
                }
            } else {
                $images = $rawImages ?? [];
            }

            if (!is_array($images) || empty($images)) {
                $errors[$field] = ["Field {$field} must be a non-empty array"];
                continue;
            }

            if (count($images) > self::MAX_IMAGES_PER_FIELD) {
                $errors[$field] = ["Maximum " . self::MAX_IMAGES_PER_FIELD . " images allowed per field"];
                continue;
            }

            // Validasi setiap image dalam array
            foreach ($images as $index => $image) {
                if (!$this->validateImageStructure($image)) {
                    $errors["{$field}.{$index}"] = ["Invalid image structure"];
                }
            }
        }

        if (!empty($errors)) {
            $validator = Validator::make([], []);
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
            throw new ValidationException($validator);
        }
    }

    private function processAndSaveImages(Request $request, $marriageId)
    {
        $imageFields = $this->getImageFields();
        $processedData = [];
        $totalSize = 0;
        $maxTotalSize = 50 * 1024 * 1024;
        
        foreach ($imageFields as $field) {
            $rawImages = $request->input($field);
            
            if (is_string($rawImages)) {
                $images = json_decode($rawImages, true);
            } else {
                $images = $rawImages ?? [];
            }
    
            $processedImages = [];
            
            foreach ($images as $index => $image) {
                $this->validateImageConstraints($image);
                
                $imageSize = strlen(base64_decode($image['data']));
                $totalSize += $imageSize;
                
                if ($totalSize > $maxTotalSize) {
                    throw new \Exception("Total upload size exceeds 50MB limit");
                }
                
                $filename = $this->saveImageToStorage($image, $field, $marriageId, $index);
                $processedImages[] = $filename;
            }
            
            // PENTING: Assign langsung sebagai array, bukan JSON string
            // Laravel akan otomatis handle JSON conversion karena casting
            $processedData[$field] = $processedImages;
        }
        
        return $processedData;
    }


    private function getImageFields()
    {
        return [
            'fotocopy_ktp', 'fotocopy_kk', 'fotocopy_akte_kelahiran',
            'fotocopy_akte_baptis_selam', 'akte_nikah_orang_tua', 
            'fotocopy_n1_n4', 'foto_berdua'
        ];
    }

    private function validateImageStructure($image)
    {
        return is_array($image) && 
               isset($image['filename']) && 
               isset($image['format']) && 
               isset($image['data']) &&
               !empty($image['filename']) &&
               !empty($image['format']) &&
               !empty($image['data']);
    }

    private function validateImageConstraints($image)
    {
        // Validate file format
        $format = strtolower(trim($image['format']));
        if (!in_array($format, self::ALLOWED_FORMATS)) {
            throw new \Exception("Invalid file format: {$image['format']}. Allowed: " . implode(', ', self::ALLOWED_FORMATS));
        }

        // Validate filename
        if (strlen($image['filename']) > 255) {
            throw new \Exception("Filename too long: {$image['filename']}");
        }

        // Validate base64
        if (!$this->isValidBase64($image['data'])) {
            throw new \Exception("Invalid base64 data for file: {$image['filename']}");
        }

        // Validate file size
        $decodedData = base64_decode($image['data']);
        $fileSize = strlen($decodedData);
        
        if ($fileSize === 0) {
            throw new \Exception("Empty file: {$image['filename']}");
        }
        
        if ($fileSize > self::MAX_FILE_SIZE) {
            $maxSizeMB = self::MAX_FILE_SIZE / (1024 * 1024);
            throw new \Exception("File too large: {$image['filename']}. Max size: {$maxSizeMB}MB");
        }

        // Validate actual file type (magic bytes)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_buffer($finfo, $decodedData);
        finfo_close($finfo);

        if (!in_array($actualMimeType, self::ALLOWED_MIMES)) {
            throw new \Exception("File content doesn't match declared format for: {$image['filename']}. Detected: {$actualMimeType}");
        }

        // Additional validation for images
        if (in_array($actualMimeType, ['image/jpeg', 'image/png'])) {
            $imageInfo = getimagesizefromstring($decodedData);
            if ($imageInfo === false) {
                throw new \Exception("Corrupted image file: {$image['filename']}");
            }
            
            // Check image dimensions (optional)
            if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
                throw new \Exception("Image dimensions too large: {$image['filename']}. Max: 4000x4000px");
            }
        }

        return true;
    }

    private function saveImageToStorage($imageData, $field, $marriageId, $index = 0)
{
    $decoded = base64_decode($imageData['data']);
    $sanitizedFilename = $this->sanitizeFilename($imageData['filename']);
    $timestamp = now()->format('YmdHis');
    $uniqueId = uniqid();
    $filename = "{$marriageId}_{$field}_{$index}_{$timestamp}_{$uniqueId}_{$sanitizedFilename}";
    $path = "marriages/{$marriageId}/{$field}/{$filename}";
    
    // Ensure directory exists
    $directory = dirname(storage_path("app/public/{$path}"));
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Save to storage
    if (!Storage::disk('public')->put($path, $decoded)) {
        throw new \Exception("Failed to save file: {$imageData['filename']}");
    }
    
    // Verify file was saved correctly
    if (!Storage::disk('public')->exists($path)) {
        throw new \Exception("File verification failed: {$imageData['filename']}");
    }
    
    // Return only the filename instead of full object
    return $filename;
}

public function getFileUrl($filename, $field, $marriageId)
{
    $path = "marriages/{$marriageId}/{$field}/{$filename}";
    
    if (Storage::disk('public')->exists($path)) {
        return Storage::disk('public')->url($path);
    }
    
    return null;
}
public function getMarriageFiles($marriageId)
{
    $marriage = Marriage::findOrFail($marriageId);
    $imageFields = $this->getImageFields();
    $files = [];
    
    foreach ($imageFields as $field) {
        $filenames = json_decode($marriage->$field ?? '[]', true);
        $files[$field] = [];
        
        foreach ($filenames as $filename) {
            $files[$field][] = [
                'filename' => $filename,
                'url' => $this->getFileUrl($filename, $field, $marriageId)
            ];
        }
    }
    
    return $files;
}



    private function cleanupUploadedFiles($marriageId)
    {
        try {
            Storage::disk('public')->deleteDirectory("marriages/{$marriageId}");
            Log::info("Cleaned up files for marriage ID: {$marriageId}");
        } catch (\Exception $e) {
            Log::warning("Failed to cleanup files for marriage ID {$marriageId}: " . $e->getMessage());
        }
    }

    private function isValidBase64($data)
    {
        if (!is_string($data) || empty($data)) {
            return false;
        }
        
        // Check if contains only valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
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
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        $filename = preg_replace('/[_\.]{2,}/', '_', $filename);
        $filename = trim($filename, '._');
        
        if (empty($filename)) {
            $filename = 'unnamed_' . time();
        }
        
        // Limit filename length
        if (strlen($filename) > 200) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 195 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }

    // Method untuk mengambil file (optional)
    public function getFile($marriageId, $field, $filename)
{
    $path = "marriages/{$marriageId}/{$field}/{$filename}";
    
    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }
    
    return Storage::disk('public')->response($path);
}

    // Method untuk delete marriage beserta files (optional)
    public function destroy($id)
    {
        try {
            $marriage = Marriage::findOrFail($id);
            
            DB::beginTransaction();
            
            // Delete files
            $this->cleanupUploadedFiles($id);
            
            // Delete record
            $marriage->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Marriage record deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error deleting marriage record {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete marriage record'
            ], 500);
        }
    }
}