<?php

namespace Tests;

use Database\Seeders\MenuSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles, the menu registry and the Karyawan reference lists are
     * infrastructure, not fixtures: every authorisation check reads the role →
     * menu mapping, and with an empty menus table the middleware (correctly)
     * refuses everything, while EmployeeRequest validates its status and type
     * fields against the reference tables. Seeding here rather than in each
     * test keeps that out of the individual cases.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->seed([RoleSeeder::class, MenuSeeder::class, ReferenceSeeder::class]);
        }
    }
}
