<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::today()->startOfMonth();

        return [
            'period_code' => $start->format('Y-m').'-'.fake()->unique()->numberBetween(1, 9999),
            'period_name' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => PayrollPeriod::OPEN,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => PayrollPeriod::CLOSED,
            'closed_at' => Carbon::now(),
        ]);
    }
}
