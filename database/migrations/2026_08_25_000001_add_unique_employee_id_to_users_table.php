<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One employee owns at most one account.
 *
 * UserRequest already rejects a duplicate link, but validation only guards the
 * form. A seeder, an import or a tinker session can bypass it, and two accounts
 * pointing at the same employee makes attendance ambiguous — AC-002 reads the
 * employee from the signed-in account, so it must resolve to exactly one row.
 *
 * employee_id stays nullable: MySQL allows any number of NULLs in a unique
 * index, so non-employee accounts (ADMIN, HRD) are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
        });
    }
};
