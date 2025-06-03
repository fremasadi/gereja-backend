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
        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap_pria');
            $table->string('nama_lengkap_wanita');
            $table->string('no_telepon');
            $table->date('tanggal_pernikahan');
            $table->json('fotocopy_ktp');          // JSON
            $table->json('fotocopy_kk');           // JSON
            $table->json('fotocopy_akte_kelahiran'); // JSON
            $table->json('fotocopy_akte_baptis_selam'); // JSON
            $table->json('akte_nikah_orang_tua');  // JSON
            $table->json('fotocopy_n1_n4');        // JSON
            $table->json('foto_berdua');            // JSON, 4 lembar foto berdua (pria kanan wanita)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marriages');
    }
};
