<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Spec §11 — sample daily-rate juru parkir. */
class EmployeeSeeder extends Seeder
{
    /**
     * The employee row behind the ADMIN login. Named here because UserSeeder
     * links to it and must also keep it out of the per-employee login loop.
     */
    public const ADMIN_CODE = 'ADM001';

    public function run(): void
    {
        $employees = [
            ['JP001', '3171010101900001', 'Budi Santoso', 'L', 150000, 'TETAP'],
            ['JP002', '3171010202920002', 'Andi Prasetyo', 'L', 175000, 'TETAP'],
            ['JP003', '3171010303940003', 'Joko Widodo', 'L', 150000, 'KONTRAK'],
            ['JP004', '3171010404960004', 'Siti Nurhaliza', 'P', 150000, 'KONTRAK'],
            ['JP005', '3171010505980005', 'Rina Marlina', 'P', 160000, 'PERCOBAAN'],
            ['JP006', '3171010606000006', 'Agus Setiawan', 'L', 150000, 'KONTRAK'],
        ];

        foreach ($employees as $index => [$code, $nik, $name, $gender, $rate, $employmentStatus]) {
            Employee::updateOrCreate(
                ['employee_code' => $code],
                [
                    'nik' => $nik,
                    'full_name' => $name,
                    'gender' => $gender,
                    'birth_place' => 'Jakarta',
                    'birth_date' => Carbon::create(1990 + $index, 1 + $index, 10 + $index),
                    'phone' => '08120000'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'address' => 'Jakarta',
                    'employment_status' => $employmentStatus,
                    'employment_type' => 'DAILY',
                    'join_date' => Carbon::create(2026, 8, 1),
                    'daily_rate' => $rate,
                    'status' => Employee::ACTIVE,
                ],
            );
        }

        // The ADMIN login doubles as an attendance user: the check-in screen
        // resolves its employee through users.employee_id, so the administrator
        // needs a row of its own. Kept out of the sample list above because it
        // is staff rather than a juru parkir, and on a zero daily rate so
        // payroll does not invent wages for it.
        Employee::updateOrCreate(
            ['employee_code' => self::ADMIN_CODE],
            [
                'full_name' => 'Administrator',
                'employment_status' => 'TETAP',
                'employment_type' => 'DAILY',
                'join_date' => Carbon::create(2026, 8, 1),
                'daily_rate' => 0,
                'status' => Employee::ACTIVE,
            ],
        );
    }
}
