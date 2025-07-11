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
        Schema::table('church_events', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_days')->nullable(); // contoh: ["tuesday", "wednesday", "thursday", "friday"]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            //
        });
    }
};
