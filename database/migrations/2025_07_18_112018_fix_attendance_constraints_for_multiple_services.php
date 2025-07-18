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
        Schema::table('attendances', function (Blueprint $table) {
            // Hapus foreign key constraints yang menggunakan index tersebut
            // Ganti 'foreign_key_name' dengan nama foreign key yang sebenarnya
            // $table->dropForeign('attendances_user_id_foreign');
            
            // Hapus unique constraint yang bermasalah
            $table->dropUnique(['user_id', 'attendance_date']);
            
            // Tambah unique constraint yang benar: user hanya bisa absen sekali per worship service
            $table->unique(['user_id', 'worship_service_id'], 'attendances_user_worship_unique');
            
            // Tambah kembali foreign key jika diperlukan
            // $table->foreign('user_id')->references('id')->on('users');
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
