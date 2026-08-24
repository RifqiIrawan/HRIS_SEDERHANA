<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Status Karyawan" — the values behind employees.status.
 *
 * Unlike the other two, this column drives behaviour: Employee::scopeActive(),
 * the assignment/roster pickers and the attendance guard all compare against
 * the literal 'ACTIVE'. So the three seeded rows are flagged is_system — new
 * statuses may be added freely (anything that is not ACTIVE simply reads as
 * non-active everywhere), but the originals cannot be renamed away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Disimpan apa adanya di employees.status');
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
        Schema::dropIfExists('employee_statuses');
    }
};
