<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §15 & §17 — the actual schedule. start_datetime / end_datetime are
 * stored explicitly (not derived from roster_date at read time) so a shift 3
 * that runs 12 Aug 22:00 → 13 Aug 06:00 resolves correctly at check-in.
 *
 * shift_id is nullable: an OFF day is a roster row with no shift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->date('roster_date');
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->string('status', 20)->default('SCHEDULED')->comment('SCHEDULED / OFF / CANCELLED');
            $table->timestamps();

            $table->unique(['employee_id', 'roster_date']);
            $table->index(['roster_date', 'location_id']);
            // The check-in lookup scans this window for the employee.
            $table->index(['employee_id', 'start_datetime', 'end_datetime'], 'shift_rosters_window_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_rosters');
    }
};
