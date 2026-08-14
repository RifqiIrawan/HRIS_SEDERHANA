<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Spec §9 — login accounts.
 *
 * The seeded password is a well-known development default. Change it before
 * the system is exposed to anything but a local machine; the installation
 * guide says so too.
 */
class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $roles = Role::pluck('id', 'role_code');

        User::updateOrCreate(
            ['email' => 'admin@hris.test'],
            [
                'name' => 'Administrator',
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $roles[Role::ADMIN],
                'status' => User::ACTIVE,
            ],
        );

        User::updateOrCreate(
            ['email' => 'hr@hris.test'],
            [
                'name' => 'Staff HR',
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $roles[Role::HR],
                'status' => User::ACTIVE,
            ],
        );

        // One login per seeded employee, so the attendance flow can be tried
        // immediately (AC-002 needs a user linked to an employee row).
        Employee::orderBy('employee_code')->get()->each(function (Employee $employee) use ($roles) {
            User::updateOrCreate(
                ['email' => strtolower($employee->employee_code).'@hris.test'],
                [
                    'name' => $employee->full_name,
                    'password' => self::DEFAULT_PASSWORD,
                    'role_id' => $roles[Role::EMPLOYEE],
                    'employee_id' => $employee->id,
                    'status' => User::ACTIVE,
                ],
            );
        });
    }
}
