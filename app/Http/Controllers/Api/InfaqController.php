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
            ->getReference('notifications/orders')
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

    public function callback(Request $request)
{
    try {
        // Ambil raw input dari request
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        
        // Verifikasi signature untuk keamanan
        if ($hashed !== $request->signature_key) {
            Log::warning('Invalid signature key for infaq callback', [
                'order_id' => $request->order_id,
                'signature_received' => $request->signature_key,
                'signature_calculated' => $hashed
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Invalid signature'
            ], 400);
        }

        // Cari infaq berdasarkan order_id
        $infaq = Infaq::where('order_id', $request->order_id)->first();
        
        if (!$infaq) {
            Log::error('Infaq not found for callback', ['order_id' => $request->order_id]);
            return response()->json([
                'status' => false,
                'message' => 'Infaq not found'
            ], 404);
        }

        // Ambil payment record terkait
        $payment = $infaq->payment;
        
        if (!$payment) {
            Log::error('Payment record not found for infaq', ['order_id' => $request->order_id]);
            return response()->json([
                'status' => false,
                'message' => 'Payment record not found'
            ], 404);
        }

        // Tentukan status berdasarkan transaction_status dari Midtrans
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? null;
        
        Log::info('Infaq callback received', [
            'order_id' => $request->order_id,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $request->payment_type,
            'gross_amount' => $request->gross_amount
        ]);

        // Update status berdasarkan transaction_status
        switch ($transactionStatus) {
            case 'capture':
                if ($fraudStatus == 'challenge') {
                    $this->updateInfaqStatus($infaq, $payment, 'challenge', $request);
                } else if ($fraudStatus == 'accept') {
                    $this->updateInfaqStatus($infaq, $payment, 'success', $request);
                }
                break;
                
            case 'settlement':
                $this->updateInfaqStatus($infaq, $payment, 'success', $request);
                break;
                
            case 'pending':
                $this->updateInfaqStatus($infaq, $payment, 'pending', $request);
                break;
                
            case 'deny':
                $this->updateInfaqStatus($infaq, $payment, 'failed', $request);
                break;
                
            case 'expire':
                $this->updateInfaqStatus($infaq, $payment, 'expired', $request);
                break;
                
            case 'cancel':
                $this->updateInfaqStatus($infaq, $payment, 'cancelled', $request);
                break;
                
            case 'refund':
                $this->updateInfaqStatus($infaq, $payment, 'refunded', $request);
                break;
                
            case 'partial_refund':
                $this->updateInfaqStatus($infaq, $payment, 'partial_refunded', $request);
                break;
                
            case 'failure':
                $this->updateInfaqStatus($infaq, $payment, 'failed', $request);
                break;
                
            default:
                Log::warning('Unknown transaction status for infaq', [
                    'order_id' => $request->order_id,
                    'transaction_status' => $transactionStatus
                ]);
                break;
        }

        return response()->json([
            'status' => true,
            'message' => 'Callback processed successfully'
        ], 200);

    } catch (\Exception $e) {
        Log::error('Infaq callback processing failed: ' . $e->getMessage(), [
            'order_id' => $request->order_id ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => false,
            'message' => 'Callback processing failed'
        ], 500);
    }
}

private function updateInfaqStatus($infaq, $payment, $status, $request)
{
    try {
        // Update infaq status
        $infaq->update([
            'status' => $status,
            'updated_at' => Carbon::now()
        ]);

        // Update payment record
        $paymentUpdateData = [
            'payment_status' => $status,
            'payment_gateway_response' => json_encode($request->all()),
            'updated_at' => Carbon::now()
        ];

        // Jika status success, update payment_date
        if ($status === 'success') {
            $paymentUpdateData['payment_date'] = Carbon::now();
            $paymentUpdateData['settlement_time'] = $request->settlement_time ?? Carbon::now();
        }

        $payment->update($paymentUpdateData);

        // Update Firebase
        $this->updateFirebaseInfaq($infaq->order_id, $status, $infaq->amount);

        // Jika pembayaran berhasil, kirim notifikasi atau email (opsional)
        if ($status === 'success') {
            $this->handleSuccessfulPayment($infaq);
        }

        Log::info('Infaq status updated successfully', [
            'order_id' => $infaq->order_id,
            'status' => $status,
            'amount' => $infaq->amount
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to update infaq status: ' . $e->getMessage(), [
            'order_id' => $infaq->order_id,
            'status' => $status,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}

private function updateFirebaseInfaq($orderId, $status, $amount)
{
    try {
        // Cari reference berdasarkan order_id di notifications/orders
        $ordersRef = $this->firebaseDatabase->getReference('notifications/orders');
        $snapshot = $ordersRef->orderByChild('order_id')->equalTo($orderId)->getSnapshot();
        
        if ($snapshot->exists()) {
            foreach ($snapshot->getValue() as $key => $value) {
                // Update record yang sudah ada di notifications/orders
                $this->firebaseDatabase
                    ->getReference('notifications/orders/' . $key)
                    ->update([
                        'status' => $status,
                        'updated_at' => Carbon::now()->timestamp,
                        'payment_date' => $status === 'success' ? Carbon::now()->timestamp : null
                    ]);
                
                Log::info('Firebase notification/orders updated', [
                    'firebase_key' => $key,
                    'order_id' => $orderId,
                    'status' => $status
                ]);
                
                // Jika status success, update total collected
                if ($status === 'success') {
                    $this->updateFirebaseTotalCollected($amount);
                }
                
                break; // Hanya update yang pertama ditemukan
            }
        } else {
            Log::warning('Firebase order record not found', [
                'order_id' => $orderId,
                'searched_in' => 'notifications/orders'
            ]);
        }

    } catch (\Exception $e) {
        Log::error('Failed to update Firebase order: ' . $e->getMessage(), [
            'order_id' => $orderId,
            'status' => $status,
            'error' => $e->getMessage()
        ]);
        // Jangan throw exception di sini agar tidak mengganggu proses utama
    }
}

private function updateFirebaseTotalCollected($amount)
{
    try {
        // Update total collected di Firebase (opsional)
        $totalRef = $this->firebaseDatabase->getReference('infaq/statistics/total_collected');
        $currentTotal = $totalRef->getSnapshot()->getValue() ?? 0;
        $newTotal = $currentTotal + $amount;
        
        $totalRef->set($newTotal);
        
        // Update juga monthly statistics
        $currentMonth = Carbon::now()->format('Y-m');
        $monthlyRef = $this->firebaseDatabase->getReference('infaq/statistics/monthly/' . $currentMonth);
        $currentMonthly = $monthlyRef->getSnapshot()->getValue() ?? 0;
        $newMonthly = $currentMonthly + $amount;
        
        $monthlyRef->set($newMonthly);

    } catch (\Exception $e) {
        Log::error('Failed to update Firebase statistics: ' . $e->getMessage());
    }
}

private function handleSuccessfulPayment($infaq)
{
    try {
        // Tambahkan logika untuk pembayaran berhasil
        // Misalnya: kirim email konfirmasi, notifikasi, dll
        
        Log::info('Infaq payment successful', [
            'order_id' => $infaq->order_id,
            'donor_name' => $infaq->donor_name,
            'amount' => $infaq->amount,
            'is_anonymous' => $infaq->is_anonymous
        ]);

        // Contoh: Update cache atau trigger event lainnya
        // Cache::forget('total_infaq_collected');
        // event(new InfaqPaymentSuccessful($infaq));

    } catch (\Exception $e) {
        Log::error('Failed to handle successful infaq payment: ' . $e->getMessage(), [
            'order_id' => $infaq->order_id
        ]);
    }
}

// Method untuk mendapatkan status infaq (opsional, untuk debugging)
public function checkStatus($orderId)
{
    try {
        $infaq = Infaq::where('order_id', $orderId)->with('payment')->first();
        
        if (!$infaq) {
            return response()->json([
                'status' => false,
                'message' => 'Infaq not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'order_id' => $infaq->order_id,
                'amount' => $infaq->amount,
                'status' => $infaq->status,
                'donor_name' => $infaq->is_anonymous ? 'Anonymous' : $infaq->donor_name,
                'payment' => $infaq->payment,
                'created_at' => $infaq->created_at,
                'updated_at' => $infaq->updated_at
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error checking infaq status: ' . $e->getMessage()
        ], 500);
    }
}
}