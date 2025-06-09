<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable'); // polymorphic relation: infaq, order, dll
            $table->string('payment_type')->nullable(); // bank_transfer, qris, dll
            $table->string('payment_gateway')->default('midtrans');
            $table->string('payment_status')->default('pending'); // pending, settlement, etc
            $table->string('payment_va_name')->nullable(); // BRI, BCA
            $table->string('payment_va_number')->nullable();
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->text('payment_gateway_response')->nullable();
            $table->timestamp('transaction_time')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('payment_date')->nullable(); // waktu berhasil bayar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
