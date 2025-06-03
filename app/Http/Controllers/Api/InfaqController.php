<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;

class InfaqController extends Controller
{
    public function __construct()
    {
        // Setup Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    // ✅ Get list of infaq (with optional filter)
    public function index(Request $request)
    {
        $query = Infaq::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(10)
        ]);
    }

    // ✅ Store new donation request and create Snap token
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'nullable|email|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:1000',
            'type' => 'required|in:infaq,sedekah,zakat,pembangunan,lainnya',
            'message' => 'nullable|string',
            'is_anonymous' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create order
        $order_id = Infaq::generateOrderId();

        $infaq = Infaq::create([
            ...$validator->validated(),
            'order_id' => $order_id,
            'status' => 'pending'
        ]);

        // Midtrans payload
        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $infaq->amount
            ],
            'customer_details' => [
                'first_name' => $infaq->donor_name,
                'email' => $infaq->donor_email,
                'phone' => $infaq->donor_phone
            ]
        ];

        // Get Snap token
        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'success' => true,
            'data' => $infaq,
            'snap_token' => $snapToken
        ]);
    }

    // ✅ Show single donation
    public function show($id)
    {
        $infaq = Infaq::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $infaq
        ]);
    }
}
