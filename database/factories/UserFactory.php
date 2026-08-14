<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => fn () => Role::firstOrCreate(
                ['role_code' => Role::EMPLOYEE],
                ['role_name' => 'Karyawan', 'status' => 'ACTIVE'],
            )->id,
            'employee_id' => null,
            'status' => User::ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(string $code): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['role_code' => $code],
                ['role_name' => $code, 'status' => 'ACTIVE'],
            )->id,
        ]);
    }

    public function admin(): static
    {
        return $this->role(Role::ADMIN);
    }

    public function hr(): static
    {
        return $this->role(Role::HR);
    }

    /** An EMPLOYEE account linked to an employee row, as AC-002 requires. */
    public function forEmployee(Employee $employee): static
    {
        return $this->role(Role::EMPLOYEE)->state(fn () => [
            'employee_id' => $employee->id,
            'name' => $employee->full_name,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => User::INACTIVE]);
    }
}
