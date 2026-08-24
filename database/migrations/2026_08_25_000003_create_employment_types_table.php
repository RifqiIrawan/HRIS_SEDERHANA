<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Tipe Kepegawaian" — the values behind employees.employment_type.
 * The MVP shipped with DAILY as the only option (spec §11); this table is what
 * lets an administrator add MONTHLY or HOURLY without a deploy.
 *
 * Payroll still multiplies daily_rate for every type (spec §35). Adding a type
 * changes what the form offers, not how a payslip is computed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Disimpan apa adanya di employees.employment_type');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE / INACTIVE');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};
