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
        Schema::create('church_events', function (Blueprint $table) {
            $table->id();
            $table->string('location_name');
            $table->text('location_address');
            $table->text('location_spesific');
            $table->date('date');
            $table->time('time');
            $table->string('theme');
            $table->string('bible_verse');
            $table->string('sermon_title');
            $table->text('sermon_content');
            $table->string('images');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_events');
    }
};
