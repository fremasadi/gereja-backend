<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class InfaqController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Daftar semua infaq dengan pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Infaq::query();

            // Filter berdasarkan status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter berdasarkan tipe
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter berdasarkan tanggal
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            $infaqs = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Data infaq berhasil diambil',
                'data' => $infaqs
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching infaq data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data infaq',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buat transaksi infaq baru
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'donor_name' => 'required|string|max:100',
                'donor_email' => 'nullable|email|max:100',
                'donor_phone' => 'nullable|string|max:20',
                'amount' => 'required|numeric|min:1000',
                'type' => 'required|in:infaq,sedekah,zakat,pembangunan,lainnya',
                'message' => 'nullable|string|max:500',
                'is_anonymous' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate order ID
            $orderId = Infaq::generateOrderId();

            // Buat record infaq
            $infaq = Infaq::create([
                'order_id' => $orderId,
                'donor_name' => $request->donor_name,
                'donor_email' => $request->donor_email,
                'donor_phone' => $request->donor_phone,
                'amount' => $request->amount,
                'type' => $request->type,
                'message' => $request->message,
                'is_anonymous' => $request->get('is_anonymous', false),
                'status' => 'pending'
            ]);

            // Parameter untuk Midtrans
            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $request->amount,
                ],
                'item_details' => [
                    [
                        'id' => 'infaq-' . $request->type,
                        'price' => (int) $request->amount,
                        'quantity' => 1,
                        'name' => ucfirst($request->type) . ' - ' . config('app.name', 'Gereja'),
                    ]
                ],
                'customer_details' => [
                    'first_name' => $request->donor_name,
                    'email' => $request->donor_email,
                    'phone' => $request->donor_phone,
                ]
            ];

            // Buat snap token
            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibuat',
                'data' => [
                    'infaq' => $infaq,
                    'snap_token' => $snapToken,
                    'order_id' => $orderId
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating infaq transaction: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi infaq',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail infaq berdasarkan order_id
     */
    public function show($orderId): JsonResponse
    {
        try {
            $infaq = Infaq::where('order_id', $orderId)->first();

            if (!$infaq) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data infaq tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data infaq berhasil diambil',
                'data' => $infaq
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching infaq detail: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail infaq',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cek status transaksi dari Midtrans
     */
    public function checkStatus($orderId): JsonResponse
    {
        try {
            $infaq = Infaq::where('order_id', $orderId)->first();

            if (!$infaq) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data infaq tidak ditemukan'
                ], 404);
            }

            // Cek status dari Midtrans
            $status = Transaction::status($orderId);
            
            // Update status di database
            $this->updateTransactionStatus($infaq, $status);

            return response()->json([
                'success' => true,
                'message' => 'Status transaksi berhasil diperbarui',
                'data' => [
                    'infaq' => $infaq->fresh(),
                    'midtrans_status' => $status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking transaction status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook callback dari Midtrans
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $notification = $request->all();
            
            $orderId = $notification['order_id'];
            $transactionStatus = $notification['transaction_status'];
            $fraudStatus = $notification['fraud_status'] ?? '';

            Log::info('Midtrans webhook received', $notification);

            $infaq = Infaq::where('order_id', $orderId)->first();

            if (!$infaq) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID tidak ditemukan'
                ], 404);
            }

            // Update berdasarkan status
            $this->updateTransactionFromWebhook($infaq, $notification);

            return response()->json([
                'success' => true,
                'message' => 'Webhook berhasil diproses'
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing webhook: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistik infaq
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_infaq' => [
                    'all_time' => Infaq::success()->sum('amount'),
                    'this_year' => Infaq::success()->thisYear()->sum('amount'),
                    'this_month' => Infaq::success()->thisMonth()->sum('amount'),
                    'today' => Infaq::success()->today()->sum('amount'),
                ],
                'count_transactions' => [
                    'all_time' => Infaq::success()->count(),
                    'this_year' => Infaq::success()->thisYear()->count(),
                    'this_month' => Infaq::success()->thisMonth()->count(),
                    'today' => Infaq::success()->today()->count(),
                ],
                'by_type' => Infaq::success()
                    ->selectRaw('type, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('type')
                    ->get(),
                'pending_transactions' => Infaq::pending()->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik infaq berhasil diambil',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching infaq statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik infaq',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method untuk update status transaksi
     */
    private function updateTransactionStatus(Infaq $infaq, $midtransResponse)
    {
        $transactionStatus = $midtransResponse->transaction_status;
        $fraudStatus = $midtransResponse->fraud_status ?? '';

        $status = 'pending';
        
        if ($transactionStatus == 'capture') {
            $status = ($fraudStatus == 'challenge') ? 'pending' : 'capture';
        } elseif ($transactionStatus == 'settlement') {
            $status = 'settlement';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $status = $transactionStatus;
        } elseif ($transactionStatus == 'failure') {
            $status = 'failure';
        }

        $infaq->update([
            'status' => $status,
            'transaction_id' => $midtransResponse->transaction_id ?? null,
            'payment_type' => $midtransResponse->payment_type ?? null,
            'payment_code' => $midtransResponse->payment_code ?? null,
            'midtrans_response' => $midtransResponse,
            'transaction_time' => $midtransResponse->transaction_time ?? null,
            'settlement_time' => ($status == 'settlement') ? now() : null
        ]);
    }

    /**
     * Helper method untuk update dari webhook
     */
    private function updateTransactionFromWebhook(Infaq $infaq, $notification)
    {
        $transactionStatus = $notification['transaction_status'];
        $fraudStatus = $notification['fraud_status'] ?? '';

        $status = 'pending';
        
        if ($transactionStatus == 'capture') {
            $status = ($fraudStatus == 'challenge') ? 'pending' : 'capture';
        } elseif ($transactionStatus == 'settlement') {
            $status = 'settlement';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $status = $transactionStatus;
        } elseif ($transactionStatus == 'failure') {
            $status = 'failure';
        }

        $infaq->update([
            'status' => $status,
            'transaction_id' => $notification['transaction_id'] ?? null,
            'payment_type' => $notification['payment_type'] ?? null,
            'payment_code' => $notification['payment_code'] ?? null,
            'midtrans_response' => $notification,
            'transaction_time' => $notification['transaction_time'] ?? null,
            'settlement_time' => ($status == 'settlement') ? now() : null
        ]);
    }
}