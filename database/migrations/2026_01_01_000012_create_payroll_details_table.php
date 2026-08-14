<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §41 & §42 — manual deduction lines only (Kasbon, Denda, Potongan Lain).
 * No payroll engine in the MVP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->string('detail_type', 20)->default('DEDUCTION');
            $table->string('description', 150);
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['payroll_id', 'detail_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
