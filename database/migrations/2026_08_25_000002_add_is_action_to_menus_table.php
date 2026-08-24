<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menus that govern a route without owning a sidebar entry.
 *
 * Printing a payslip is an action on one payroll row, not a screen: its route
 * takes a {payroll} parameter, so the sidebar could not even build a link for
 * it. It still needs a row here, because MenuAccessService denies by default —
 * an unclaimed route is refused — and because the Role screen reads this table
 * to draw the access matrix, which is where the mapping belongs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->boolean('is_action')
                ->default(false)
                ->after('requires_employee')
                ->comment('Governs routes but renders no sidebar link');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('is_action');
        });
    }
};
