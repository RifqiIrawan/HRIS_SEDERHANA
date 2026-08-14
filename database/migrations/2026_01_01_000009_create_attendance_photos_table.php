<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §26 — the photo itself lives on the "attendance" storage disk; this
 * table only records where it is and what it is. No base64 in MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->string('photo_type', 20)->comment('CHECK_IN / CHECK_OUT');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('bytes');
            $table->timestamps();

            $table->unique(['attendance_id', 'photo_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_photos');
    }
};
