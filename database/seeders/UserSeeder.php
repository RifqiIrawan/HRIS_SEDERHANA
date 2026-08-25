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

        // Linked so the ADMIN account can use the check-in screen: unlinked, it
        // only ever gets a 403 there (AC-002 reads the employee from this
        // column). EmployeeSeeder owns the row.
        $adminEmployeeId = Employee::where('employee_code', EmployeeSeeder::ADMIN_CODE)->value('id');

        User::updateOrCreate(
            ['email' => 'admin@parkops.test'],
            [
                'name' => 'Administrator',
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $roles[Role::ADMIN],
                'employee_id' => $adminEmployeeId,
                'status' => User::ACTIVE,
            ],
        );

        User::updateOrCreate(
            ['email' => 'hr@parkops.test'],
            [
                'name' => 'Staff HR',
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $roles[Role::HR],
                'status' => User::ACTIVE,
            ],
        );

        // One login per seeded employee, so the attendance flow can be tried
        // immediately (AC-002 needs a user linked to an employee row). The
        // administrator's row is excluded: it already has the admin login, and
        // a second user on the same employee would collide on the one-row-per-
        // employee-per-day attendance index.
        Employee::where('employee_code', '!=', EmployeeSeeder::ADMIN_CODE)
            ->orderBy('employee_code')
            ->get()
            ->each(function (Employee $employee) use ($roles) {
                User::updateOrCreate(
                    ['email' => strtolower($employee->employee_code).'@parkops.test'],
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
