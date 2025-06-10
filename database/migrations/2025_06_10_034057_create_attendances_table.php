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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi ke users
            $table->date('attendance_date'); // Tanggal kehadiran (biasanya Minggu)
            $table->timestamp('check_in_at')->nullable();  // Waktu check-in
            $table->timestamp('check_out_at')->nullable(); // Waktu check-out
            $table->text('note')->nullable(); // Catatan opsional
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']); // Tidak bisa dua kali absen di hari yang sama
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
