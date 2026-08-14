<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftRoster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ShiftRoster>
 */
class ShiftRosterFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::today();

        return [
            'employee_id' => Employee::factory(),
            'location_id' => Location::factory(),
            'shift_id' => Shift::factory(),
            'roster_date' => $date->toDateString(),
            'start_datetime' => $date->copy()->setTime(6, 0),
            'end_datetime' => $date->copy()->setTime(14, 0),
            'status' => ShiftRoster::SCHEDULED,
        ];
    }

    /**
     * Derives start/end from the shift the way RosterService does, so tests
     * never hand-roll a cross-day boundary and get it subtly wrong.
     */
    public function forShift(Shift $shift, Carbon $date): static
    {
        return $this->state(fn () => [
            'shift_id' => $shift->id,
            'roster_date' => $date->toDateString(),
            'start_datetime' => $shift->startDatetimeFor($date),
            'end_datetime' => $shift->endDatetimeFor($date),
            'status' => ShiftRoster::SCHEDULED,
        ]);
    }

    public function off(): static
    {
        return $this->state(fn () => [
            'shift_id' => null,
            'start_datetime' => null,
            'end_datetime' => null,
            'status' => ShiftRoster::OFF,
        ]);
    }
}
