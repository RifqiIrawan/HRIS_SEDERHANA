<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Order matters: roles before users, employees before the users that link to
 * them, and all master data before the demo roster/attendance.
 *
 * Skip the sample roster and attendance with:
 *     php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --no-interaction
 *     HRIS_SEED_DEMO=false php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ShiftSeeder::class,
            LocationSeeder::class,
            EmployeeSeeder::class,
            UserSeeder::class,
        ]);

        if (env('HRIS_SEED_DEMO', true)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
