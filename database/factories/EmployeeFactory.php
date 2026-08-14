<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_code' => 'JP'.fake()->unique()->numberBetween(100, 99999),
            'nik' => fake()->unique()->numerify('################'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_place' => 'Jakarta',
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'employment_status' => 'KONTRAK',
            'employment_type' => 'DAILY',
            'join_date' => now()->subMonths(6),
            'daily_rate' => 150000,
            'status' => Employee::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Employee::INACTIVE]);
    }

    public function dailyRate(float $rate): static
    {
        return $this->state(fn () => ['daily_rate' => $rate]);
    }
}
