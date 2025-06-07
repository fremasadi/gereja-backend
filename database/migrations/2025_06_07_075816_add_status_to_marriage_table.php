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
        Schema::table('marriages', function (Blueprint $table) {
            $table->boolean('status')->default(false)->after('foto_berdua'); // sesuaikan posisi `after` jika perlu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marriage', function (Blueprint $table) {
            //
        });
    }
};
