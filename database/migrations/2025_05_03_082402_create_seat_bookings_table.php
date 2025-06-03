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
        Schema::create('seat_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worship_service_id')->constrained();
            $table->foreignId('seat_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('service_date');
            $table->string('status')->default('booked');
            // $table->text('notes')->nullable();
            // $table->dateTime('check_in_time')->nullable();
            $table->string('booking_code')->unique();
            $table->timestamps();
            
            // Mencegah kursi yang sama dipesan dua kali pada layanan dan tanggal yang sama
            $table->unique(['seat_id', 'worship_service_id', 'service_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_bookings');
    }
};
