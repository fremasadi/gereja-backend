<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Facades\Auth;

class InfaqController extends Controller
{  
    public function create(Request $request)
    {
        $user = $request->user();

        $allowedTypes = ['Persembahan'];

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'message' => 'nullable|string',
            'is_anonymous' => 'boolean',
            'payment_type' => 'required|in:bank_transfer,qris,gopay',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = Infaq::generateOrderId();

        $infaq = Infaq::create([
            'order_id' => $orderId,
            'donor_name' => $user->name,
            'donor_email' => $user->email,
            'donor_phone' => $user->phone ?? null,
            'amount' => $request->amount,
            'message' => $request->message,
            'is_anonymous' => $request->is_anonymous ?? false,
            'status' => 'pending',
            'payment_type' => $request->payment_type,
        ]);

        // Konfigurasi Midtrans - Move this BEFORE using Snap
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_ENV') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Define transaction data parameters
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $infaq->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '081234567890'
            ]
        ];

        try {
            // Create Midtrans transaction
            $midtransResponse = Snap::createTransaction($params);
            
            // Handle response based on its type
            if (is_array($midtransResponse)) {
                $responseData = $midtransResponse;
            } elseif (is_object($midtransResponse)) {
                // Convert object to array
                $responseData = json_decode(json_encode($midtransResponse), true);
            } else {
                // If it's a string (JSON), decode it
                $responseData = json_decode($midtransResponse, true);
            }

            // Safely access array elements
            $va = isset($responseData['va_numbers']) && is_array($responseData['va_numbers']) 
                ? ($responseData['va_numbers'][0] ?? []) 
                : [];

            // Create payment record with safe array access
            $payment = $infaq->payment()->create([
                'payment_type' => $responseData['payment_type'] ?? null,
                'payment_status' => $responseData['transaction_status'] ?? 'pending',
                'payment_gateway_response' => $responseData,
                'payment_va_name' => $va['bank'] ?? null,
                'payment_va_number' => $va['va_number'] ?? null,
                'gross_amount' => $responseData['gross_amount'] ?? $infaq->amount,
                'transaction_time' => $responseData['transaction_time'] ?? now(),
                'expired_at' => $responseData['expiry_time'] ?? null,
            ]);

            // Get Snap Token
            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'status' => true,
                'message' => 'Infaq created successfully',
                'snap_token' => $snapToken,
                'infaq' => $infaq,
                'payment' => $payment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create Snap Token: ' . $e->getMessage(),
            ], 500);
        }
    }
}