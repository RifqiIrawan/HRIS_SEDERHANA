<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the schema against a defect that no functional test would ever catch.
 *
 * MariaDB has no native JSON type: `$table->json(...)` compiles to
 * `longtext ... CHECK (json_valid(col))`. The column behaves perfectly — every
 * read, write and cast works — so the application cannot tell the difference.
 * What breaks is *outside* the application: several database GUIs cannot parse
 * that inline CHECK and refuse to open the table, which leaves the audit trail
 * unreadable to anyone not writing SQL by hand.
 *
 * The failure therefore has to be caught structurally. If this test fails, a
 * migration has introduced `->json()`; declare the column as `longText()` and
 * let the model's `array` cast do the encoding, exactly as the existing ones do.
 */
class SchemaPortabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function no_column_carries_a_json_valid_check_constraint(): void
    {
        if (! $this->onMariaDb()) {
            $this->markTestSkipped('Only MariaDB compiles JSON columns into a CHECK constraint.');
        }

        $offenders = collect(DB::select(
            'SELECT TABLE_NAME AS t, CONSTRAINT_NAME AS c
               FROM information_schema.CHECK_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND CHECK_CLAUSE LIKE ?',
            ['%json_valid%'],
        ))->map(fn (object $row) => "{$row->t}.{$row->c}")->all();

        $this->assertSame(
            [],
            $offenders,
            'These columns were declared with $table->json(), which MariaDB turns into a '
            .'CHECK (json_valid(...)) that some database GUIs cannot parse: '
            .implode(', ', $offenders)
            .'. Use $table->longText() plus an `array` cast on the model instead.',
        );
    }

    private function onMariaDb(): bool
    {
        return str_contains(
            strtolower((string) DB::selectOne('SELECT VERSION() AS v')->v),
            'mariadb',
        );
    }
}
