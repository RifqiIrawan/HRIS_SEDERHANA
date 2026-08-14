<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/** Spec §10 — the three roles the authorisation layer knows about. */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [Role::ADMIN, 'Administrator'],
            [Role::HR, 'Human Resource'],
            [Role::EMPLOYEE, 'Karyawan'],
        ];

        foreach ($roles as [$code, $name]) {
            Role::updateOrCreate(
                ['role_code' => $code],
                ['role_name' => $name, 'status' => 'ACTIVE'],
            );
        }
    }
}
