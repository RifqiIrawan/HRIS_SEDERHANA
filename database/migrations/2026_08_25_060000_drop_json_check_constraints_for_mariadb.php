<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the three JSON columns as plain LONGTEXT, without MariaDB's
 * `CHECK (json_valid(...))`.
 *
 * MariaDB has no native JSON type: `$table->json(...)` becomes
 * `longtext ... COLLATE utf8mb4_bin CHECK (json_valid(col))`. The column works
 * perfectly — reads, writes and casts were all verified — but that inline CHECK
 * is what several database GUIs (HeidiSQL among them) fail to parse, and the
 * failure is not cosmetic: the table cannot be opened at all, which makes the
 * audit trail unreadable to anyone not writing SQL by hand.
 *
 * Dropping it is safe here because nothing depends on it:
 *
 *   - No query anywhere uses whereJsonContains, JSON_EXTRACT or the `->>`
 *     operator. Every one of these columns is decoded in PHP by an Eloquent
 *     `array` cast, which is also the only thing that ever writes them.
 *   - The cast cannot produce invalid JSON, so the database-level guarantee was
 *     protecting against a writer that does not exist.
 *
 * The collation stays utf8mb4_bin, so the *only* difference from before is the
 * absent constraint — byte storage and comparison semantics are untouched.
 *
 * On MySQL proper this migration does nothing: there is no such constraint,
 * because JSON is a real type there.
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: string, 2: bool}> table, column, nullable */
    private const COLUMNS = [
        ['audit_logs', 'context', true],
        ['menus', 'route_patterns', false],
        ['menu_role', 'actions', true],
    ];

    public function up(): void
    {
        if (! $this->isMariaDb()) {
            return;
        }

        foreach (self::COLUMNS as [$table, $column, $nullable]) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            // MODIFY is the whole operation. The CHECK belongs to the column
            // definition rather than to the table, so redefining the column
            // removes it — verified against information_schema, which reported
            // the constraint before and nothing after.
            //
            // Do not reach for `DROP CONSTRAINT` here. It looks right, because
            // information_schema.CHECK_CONSTRAINTS does list the constraint
            // under the column's own name, but MariaDB refuses it with
            // "Can't DROP CONSTRAINT `context`; check that it exists" — the row
            // is visible, the object is not independently droppable.
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin %s',
                $table,
                $column,
                $nullable ? 'NULL' : 'NOT NULL',
            ));
        }
    }

    public function down(): void
    {
        if (! $this->isMariaDb()) {
            return;
        }

        // Restoring the JSON alias re-creates the CHECK. MariaDB validates every
        // existing row as it does so, which is exactly the assertion we want: if
        // anything wrote malformed JSON while the constraint was off, this fails
        // loudly rather than quietly re-enabling a guarantee that no longer holds.
        foreach (self::COLUMNS as [$table, $column, $nullable]) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` JSON %s',
                $table,
                $column,
                $nullable ? 'NULL' : 'NOT NULL',
            ));
        }
    }

    private function isMariaDb(): bool
    {
        return str_contains(
            strtolower((string) DB::selectOne('SELECT VERSION() AS v')->v),
            'mariadb',
        );
    }
};
