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
        Schema::create('infaqs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable();
            $table->string('donor_name');
            $table->string('donor_email')->nullable();
            $table->string('donor_phone')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['infaq', 'sedekah', 'zakat', 'pembangunan', 'lainnya'])->default('infaq');
            $table->text('message')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'settlement', 'capture', 'deny', 'cancel', 'expire', 'failure'])->default('pending');
            $table->string('payment_type')->nullable();
            $table->string('payment_code')->nullable();
            $table->json('midtrans_response')->nullable();
            $table->timestamp('transaction_time')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infaqs');
    }
};
