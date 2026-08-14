<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §14 — Master Shift. Shift 3 (22:00 → 06:00) has cross_day = true, which
 * is what RosterService uses to push end_datetime onto the next calendar day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_code', 30)->unique();
            $table->string('shift_name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('cross_day')->default(false);
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
