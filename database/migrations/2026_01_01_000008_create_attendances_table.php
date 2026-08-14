<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §31 — Attendance. Every GPS reading the backend accepted is kept
 * (AC-013/AC-014) together with the distance the backend itself computed —
 * the number the browser reported is never stored as fact.
 *
 * attendance_date always equals the roster_date of the shift, so a shift 3
 * that ends at 06:00 the next morning still belongs to the day it started.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('roster_id')->nullable()->constrained('shift_rosters')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->date('attendance_date');

            $table->dateTime('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->decimal('check_in_distance', 10, 2)->nullable();
            $table->string('check_in_photo')->nullable();

            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->decimal('check_out_distance', 10, 2)->nullable();
            $table->string('check_out_photo')->nullable();

            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->string('status', 20)->default('INCOMPLETE')
                ->comment('PRESENT / LATE / ABSENT / INCOMPLETE');
            $table->timestamps();

            // AC-012: one attendance row per employee per shift day.
            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['location_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
