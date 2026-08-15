<?php

namespace Tests;

use Database\Seeders\MenuSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles and the menu registry are infrastructure, not fixtures: every
     * authorisation check reads the role → menu mapping, and with an empty
     * menus table the middleware (correctly) refuses everything. Seeding here
     * rather than in each test keeps that out of the individual cases.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->seed([RoleSeeder::class, MenuSeeder::class]);
        }
    }
}
