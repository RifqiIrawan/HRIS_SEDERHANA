<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which verbs a role holds on a menu, not merely whether it holds the menu.
 *
 * The mapping used to be one bit: the row existed or it did not, and reaching
 * a screen meant reaching every route on it — ticking "Karyawan" handed over
 * deletion with no way to withhold it. This column is that missing half.
 *
 * NULL is deliberately not the empty set. An existing row means "all of it",
 * which is what those rows have always granted, so the column can land on a
 * populated table without silently revoking anything. Only a write from the
 * Role screen turns NULL into an explicit list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_role', function (Blueprint $table) {
            // longText, not json. On MariaDB — which is what XAMPP ships — the
            // JSON type is an alias for exactly this column plus a
            // `CHECK (json_valid(actions))`, and that inline CHECK is what stops
            // several database GUIs from opening the table at all. See the
            // 060000 migration, which strips the same constraint from the two
            // JSON columns that predate it; this column is declared correctly
            // from the start instead, because it is created *after* that sweep
            // and would otherwise reintroduce the problem on every fresh migrate.
            //
            // Nothing is lost: MenuRole casts `actions` to an array and is the
            // only reader and writer, so the database-level validation was
            // guarding against a writer that does not exist. No query anywhere
            // uses JSON path operators on it.
            $table->longText('actions')
                ->nullable()
                ->after('role_id')
                ->comment('Granted action keys as JSON; NULL = every action the menu offers');
        });
    }

    public function down(): void
    {
        Schema::table('menu_role', function (Blueprint $table) {
            $table->dropColumn('actions');
        });
    }
};
