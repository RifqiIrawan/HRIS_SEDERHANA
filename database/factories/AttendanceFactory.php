<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::today();

        return [
            'employee_id' => Employee::factory(),
            'location_id' => Location::factory(),
            'roster_id' => null,
            'attendance_date' => $date->toDateString(),
            'check_in_at' => $date->copy()->setTime(6, 2),
            'check_in_latitude' => -6.1944000,
            'check_in_longitude' => 106.8229000,
            'check_in_accuracy' => 7.2,
            'check_in_distance' => 4.8,
            'check_out_at' => $date->copy()->setTime(14, 1),
            'check_out_latitude' => -6.1944000,
            'check_out_longitude' => 106.8229000,
            'check_out_accuracy' => 6.5,
            'check_out_distance' => 3.9,
            'late_minutes' => 0,
            'status' => Attendance::PRESENT,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function late(int $minutes = 25): static
    {
        return $this->state(fn () => [
            'late_minutes' => $minutes,
            'status' => Attendance::LATE,
        ]);
    }

    /** Checked in but never checked out — not payable (spec §39). */
    public function incomplete(): static
    {
        return $this->state(fn () => [
            'check_out_at' => null,
            'check_out_latitude' => null,
            'check_out_longitude' => null,
            'check_out_accuracy' => null,
            'check_out_distance' => null,
            'status' => Attendance::INCOMPLETE,
        ]);
    }

    public function on(Carbon $date): static
    {
        return $this->state(fn () => [
            'attendance_date' => $date->toDateString(),
            'check_in_at' => $date->copy()->setTime(6, 2),
            'check_out_at' => $date->copy()->setTime(14, 1),
        ]);
    }
}
