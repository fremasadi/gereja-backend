<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\CoreApi;
use Midtrans\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class InfaqController extends Controller
{
    private $firebaseDatabase;

    public function __construct()
    {
        $this->firebaseDatabase = (new Factory)
        ->withServiceAccount(config('firebase.firebase.service_account'))
        ->withDatabaseUri('https://fre-kantin-default-rtdb.firebaseio.com') // Pastikan menggunakan URL yang benar
        ->createDatabase();

        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_ENV') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function generateOrderId()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $randomString = 'INFAQ-';
            for ($i = 0; $i < 7; $i++) {
                $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
            }
            $existingOrder = Infaq::where('order_id', $randomString)->exists();
        } while ($existingOrder);

        return $randomString;
    }
  
    public function create(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'message' => 'nullable|string',
            'is_anonymous' => 'boolean',
            'payment_type' => 'required|in:BANK_TRANSFER,QRIS,GOPAY',
            'bank' => 'required_if:payment_type,BANK_TRANSFER|in:bri,bni,bca,mandiri,permata'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validasi bank untuk BANK_TRANSFER
        $paymentType = $request->payment_type;
        $bank = $request->bank;

        if ($paymentType === 'BANK_TRANSFER' && !$bank) {
            return response()->json([
                'status' => false, 
                'message' => 'Bank is required for bank transfer payment'
            ], 400);
        }

        $orderId = $this->generateOrderId();

        try {
            $infaq = Infaq::create([
                'order_id' => $orderId,
                'donor_name' => $user->name,
                'donor_email' => $user->email,
                'donor_phone' => $user->phone ?? null,
                'amount' => $request->amount,
                'message' => $request->message,
                'is_anonymous' => $request->is_anonymous ?? false,
                'status' => 'pending',
                'payment_type' => $paymentType,
            ]);

            // Proses pembayaran menggunakan CoreApi seperti OrderController
            $paymentGatewayResponse = $this->processPayment($paymentType, $infaq->amount, $orderId, $bank, $user);

            if (isset($paymentGatewayResponse['error'])) {
                throw new \Exception($paymentGatewayResponse['error']);
            }

            // Create payment record
            $payment = $infaq->payment()->create([
                'payment_status' => 'pending',
                'payment_type' => $paymentType,
                'payment_gateway' => 'midtrans',
                'payment_gateway_reference_id' => $orderId,
                'payment_gateway_response' => json_encode($paymentGatewayResponse['response']),
                'gross_amount' => $infaq->amount,
                'payment_proof' => null,
                'payment_date' => Carbon::now(),
                'expired_at' => Carbon::now()->addHours(1),
                'payment_va_name' => $paymentGatewayResponse['va_bank'],
                'payment_va_number' => $paymentGatewayResponse['va_number'],
                'payment_qr_url' => $paymentGatewayResponse['qr_string'],
                'payment_deeplink' => $paymentGatewayResponse['deeplink_redirect'],
            ]);

            $this->firebaseDatabase
            ->getReference('infaq/persembahan')
            ->push([
                'order_id' => $orderId,
                'total_amount' => $request->amount,
                'status' =>'pending',
                'timestamp' => Carbon::now()->timestamp,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Infaq created successfully',
                'main_order_id' => $orderId,
                'infaq' => $infaq,
                'payment' => $payment
            ], 200);

        } catch (\Exception $e) {
            Log::error('Infaq creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error creating infaq or payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function processPayment($paymentType, $totalAmount, $orderId, $bank = null, $user)
    {
        // Log untuk debugging
        Log::info('Midtrans Config Check', [
            'server_key_exists' => !empty(env('MIDTRANS_SERVER_KEY')),
            'server_key_length' => strlen(env('MIDTRANS_SERVER_KEY')),
            'is_production' => env('MIDTRANS_ENV') === 'production'
        ]);

        $transaction_details = [
            'order_id' => $orderId,
            'gross_amount' => $totalAmount,
        ];

        $item_details = [
            [
                'id' => 'infaq-1',
                'price' => $totalAmount,
                'quantity' => 1,
                'name' => 'Infaq #' . $orderId,
            ]
        ];

        $customer_details = [
            'first_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? 'N/A',
        ];

        // Add custom expiry
        $custom_expiry = [
            'expiry_duration' => 1, // Duration in hours
            'unit' => 'hour', // Units can be 'minute', 'hour', or 'day'
        ];

        // Base transaction data
        $transaction_data = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
            'custom_expiry' => $custom_expiry,
        ];

        // Set payment method based on type
        switch ($paymentType) {
            case 'BANK_TRANSFER':
                $transaction_data['payment_type'] = 'bank_transfer';
                $transaction_data['bank_transfer'] = [
                    'bank' => strtolower($bank)
                ];
                break;
                
            case 'QRIS':
                $transaction_data['payment_type'] = 'qris';
                break;
                
            case 'GOPAY':
                $transaction_data['payment_type'] = 'gopay';
                break;
                
            default:
                $transaction_data['payment_type'] = 'bank_transfer';
                break;
        }

        try {
            $response = CoreApi::charge($transaction_data);

            $result = [
                'response' => $response,
                'va_bank' => null,
                'va_number' => null,
                'redirect_url' => null,
                'qr_string' => null,
                'deeplink_redirect' => null
            ];

            // Handle different payment types response
            if ($response->payment_type === 'bank_transfer') {
                if (isset($response->va_numbers) && !empty($response->va_numbers)) {
                    $result['va_bank'] = $response->va_numbers[0]->bank;
                    $result['va_number'] = $response->va_numbers[0]->va_number;
                } elseif (isset($response->permata_va_number)) {
                    $result['va_bank'] = 'permata';
                    $result['va_number'] = $response->permata_va_number;
                }
            } elseif ($response->payment_type === 'qris') {
                // Handle QRIS response
                if (isset($response->actions)) {
                    foreach ($response->actions as $action) {
                        if ($action->name === 'generate-qr-code') {
                            $result['qr_string'] = $action->url;
                            break;
                        }
                    }
                }
            } elseif ($response->payment_type === 'gopay') {
                // Handle GoPay response
                if (isset($response->actions)) {
                    foreach ($response->actions as $action) {
                        if ($action->name === 'generate-qr-code') {
                            $result['qr_string'] = $action->url;
                        } elseif ($action->name === 'deeplink-redirect') {
                            $result['deeplink_redirect'] = $action->url;
                        }
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Midtrans payment processing failed: ' . $e->getMessage());
            return ['error' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }
}