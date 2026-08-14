<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §37 & §44 — a period walks OPEN → PROCESSED → CLOSED. Once CLOSED it
 * cannot be regenerated until an ADMIN reopens it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_code', 30)->unique();
            $table->string('period_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('OPEN')->comment('OPEN / PROCESSED / CLOSED');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
