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
        Schema::table('infaqs', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'expired',
                'cancelled',
                'refunded',
                'partial_refunded',
                'challenge'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
