<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAttendancesTableForMultipleServices extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop constraint lama yang hanya berdasarkan user_id + attendance_date
            $table->dropUnique(['user_id', 'attendance_date']);
            
            // Tambah constraint baru: user_id + worship_service_id (unique per service)
            // Ini memungkinkan user attend multiple services dalam satu hari
            $table->unique(['user_id', 'worship_service_id'], 'attendances_user_worship_service_unique');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Kembalikan ke constraint lama
            $table->dropUnique('attendances_user_worship_service_unique');
            $table->unique(['user_id', 'attendance_date'], 'attendances_user_id_attendance_date_unique');
        });
    }
}