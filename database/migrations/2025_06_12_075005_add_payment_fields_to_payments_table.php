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
        Schema::table('payments', function (Blueprint $table) {
            // Tambahkan kolom yang hilang jika belum ada
            if (!Schema::hasColumn('payments', 'payment_qr_url')) {
                $table->text('payment_qr_url')->nullable()->after('payment_va_number');
            }
            
            if (!Schema::hasColumn('payments', 'payment_deeplink')) {
                $table->text('payment_deeplink')->nullable()->after('payment_qr_url');
            }
            
            if (!Schema::hasColumn('payments', 'payment_gateway_reference_id')) {
                $table->string('payment_gateway_reference_id')->nullable()->after('payment_gateway');
            }
            
            if (!Schema::hasColumn('payments', 'payment_proof')) {
                $table->text('payment_proof')->nullable()->after('gross_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
