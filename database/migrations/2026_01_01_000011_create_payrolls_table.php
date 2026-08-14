<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §40 — one row per employee per period. daily_rate is snapshotted at
 * generate time so a later raise never silently rewrites a past payslip.
 *
 * present_days / late_days are kept alongside working_days purely so the
 * report can show how the total was reached (working_days = present + late).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->unsignedSmallInteger('working_days')->default(0);
            $table->decimal('daily_rate', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT / FINAL');
            $table->timestamps();

            $table->unique(['period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
