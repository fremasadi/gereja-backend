<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Facades\Auth;
class InfaqController extends Controller{  

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

    $midtransResponse = Snap::createTransaction($params);
    $responseData = $midtransResponse->jsonSerialize(); // konversi ke array

    $va = $responseData['va_numbers'][0] ?? [];

    $infaq->payment()->create([
        'payment_type' => $responseData['payment_type'],
        'payment_status' => $responseData['transaction_status'],
        'payment_gateway_response' => $responseData,
        'payment_va_name' => $va['bank'] ?? null,
        'payment_va_number' => $va['va_number'] ?? null,
        'gross_amount' => $responseData['gross_amount'],
        'transaction_time' => $responseData['transaction_time'],
        'expired_at' => $responseData['expiry_time'] ?? null,
    ]);

    // Konfigurasi Midtrans
    Config::$serverKey = env('MIDTRANS_SERVER_KEY');
    Config::$isProduction = env('MIDTRANS_ENV') === 'production';
    Config::$isSanitized = true;
    Config::$is3ds = true;

    $transactionData = [
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
        $snapToken = Snap::getSnapToken($transactionData);

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
