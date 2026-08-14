<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §11 — Master Karyawan. employment_type is DAILY for the whole MVP;
 * daily_rate is the only figure payroll multiplies by (spec §35).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 30)->unique();
            $table->string('nik', 30)->nullable()->unique();
            $table->string('full_name', 150);
            $table->string('photo')->nullable();
            $table->string('gender', 1)->nullable()->comment('L / P');
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('employment_status', 20)->default('PERCOBAAN')->comment('PERCOBAAN / KONTRAK / TETAP');
            $table->string('employment_type', 20)->default('DAILY')->comment('MVP: DAILY only');
            $table->date('join_date')->nullable();
            $table->decimal('daily_rate', 15, 2)->default(0);
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE / INACTIVE / RESIGNED');
            $table->timestamps();

            $table->index('status');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
