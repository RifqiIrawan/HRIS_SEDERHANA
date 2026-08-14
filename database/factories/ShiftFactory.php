<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shift_code' => 'S'.fake()->unique()->numberBetween(1, 9999),
            'shift_name' => 'Shift Pagi',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'cross_day' => false,
            'late_tolerance_minutes' => 15,
            'status' => Shift::ACTIVE,
        ];
    }

    /** Spec §14 shift 3 — 22:00 → 06:00 the next day. */
    public function night(): static
    {
        return $this->state(fn () => [
            'shift_name' => 'Shift Malam',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'cross_day' => true,
        ]);
    }
}
