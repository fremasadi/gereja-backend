<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Ambil informasi foreign key yang menggunakan index ini
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'attendances' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND COLUMN_NAME = 'user_id'
        ");

        Schema::table('attendances', function (Blueprint $table) use ($foreignKeys) {
            // Hapus foreign key constraints yang menggunakan user_id
            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                } catch (Exception $e) {
                    // Jika gagal, coba format lain
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (Exception $e2) {
                        // Log error tapi lanjutkan
                        \Log::warning('Could not drop foreign key: ' . $e2->getMessage());
                    }
                }
            }
            
            // Hapus unique constraint yang bermasalah
            $table->dropUnique(['user_id', 'attendance_date']);
            
            // Tambah unique constraint yang benar
            $table->unique(['user_id', 'worship_service_id'], 'attendances_user_worship_unique');
            
            // Tambah kembali foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
