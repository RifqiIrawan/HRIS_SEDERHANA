<?php

namespace App\Services;

use App\Exceptions\RosterException;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftRoster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Spec §16 — turns a repeating pattern like "1,2,3,OFF" into concrete roster
 * rows across a date range.
 *
 * The pattern cycles by position in the range, so day 5 of a 4-token pattern
 * gets token 1 again. Each generated row stores absolute start/end datetimes
 * (spec §17) rather than leaving the attendance module to re-derive them.
 */
class RosterService
{
    /**
     * @param  array<int, int>  $employeeIds
     * @param  array<int, string>  $pattern  shift codes, or the literal "OFF"
     * @return array{created: int, updated: int, skipped: int, days: int, messages: array<int, string>}
     */
    public function generate(
        array $employeeIds,
        int $locationId,
        Carbon $startDate,
        Carbon $endDate,
        array $pattern,
        bool $overwrite = false,
    ): array {
        $pattern = array_values(array_filter(array_map('trim', $pattern), fn ($t) => $t !== ''));

        if ($pattern === []) {
            throw RosterException::emptyPattern();
        }

        $location = Location::findOrFail($locationId);

        if (! $location->isActive()) {
            throw RosterException::inactiveLocation();
        }

        $shifts = $this->resolvePattern($pattern);
        $employees = Employee::active()->whereIn('id', $employeeIds)->get();

        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $messages = [];

        DB::transaction(function () use (
            $employees, $location, $shifts, $pattern, $startDate, $endDate, $overwrite,
            &$created, &$updated, &$skipped, &$messages
        ) {
            foreach ($employees as $employee) {
                $dayIndex = 0;

                for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
                    $token = $pattern[$dayIndex % count($pattern)];
                    $dayIndex++;

                    $shift = $shifts[$token] ?? null;
                    $row = $this->buildRow($employee, $location, $shift, $date);

                    $existing = ShiftRoster::with('attendance')
                        ->where('employee_id', $employee->id)
                        ->whereDate('roster_date', $date)
                        ->first();

                    if (! $existing) {
                        ShiftRoster::create($row);
                        $created++;

                        continue;
                    }

                    if (! $overwrite) {
                        $skipped++;

                        continue;
                    }

                    // Rewriting a day the employee already clocked in on would
                    // detach the attendance from the shift it was measured
                    // against, so those days are always left alone.
                    if ($existing->attendance) {
                        $skipped++;
                        $messages[] = sprintf(
                            '%s %s dilewati: sudah ada absensi.',
                            $employee->employee_code,
                            $date->format('d-m-Y'),
                        );

                        continue;
                    }

                    $existing->update($row);
                    $updated++;
                }
            }
        });

        $days = (int) $startDate->diffInDays($endDate) + 1;

        AuditLog::record('roster.generate', $location, sprintf(
            'Generate roster %s s/d %s untuk %d karyawan (pola: %s)',
            $startDate->format('d-m-Y'),
            $endDate->format('d-m-Y'),
            $employees->count(),
            implode(',', $pattern),
        ), compact('created', 'updated', 'skipped', 'days'));

        return compact('created', 'updated', 'skipped', 'days', 'messages');
    }

    /**
     * Map each pattern token to a Shift, or to null for an OFF day.
     *
     * Resolved once up front so an unknown code fails before a single row is
     * written, rather than half-way through a 31-day range.
     *
     * @param  array<int, string>  $pattern
     * @return array<string, ?Shift>
     */
    private function resolvePattern(array $pattern): array
    {
        $tokens = array_unique($pattern);
        $codes = array_values(array_filter($tokens, fn ($t) => strtoupper($t) !== Shift::OFF));

        $shifts = Shift::active()->whereIn('shift_code', $codes)->get()->keyBy('shift_code');

        $resolved = [];

        foreach ($tokens as $token) {
            if (strtoupper($token) === Shift::OFF) {
                $resolved[$token] = null;

                continue;
            }

            $resolved[$token] = $shifts->get($token) ?? throw RosterException::unknownShiftCode($token);
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(Employee $employee, Location $location, ?Shift $shift, Carbon $date): array
    {
        return [
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'shift_id' => $shift?->id,
            'roster_date' => $date->toDateString(),
            'start_datetime' => $shift?->startDatetimeFor($date),
            'end_datetime' => $shift?->endDatetimeFor($date),
            'status' => $shift ? ShiftRoster::SCHEDULED : ShiftRoster::OFF,
        ];
    }

    /**
     * Preview of what a pattern produces, used by the UI before committing.
     *
     * @param  array<int, string>  $pattern
     * @return Collection<int, array{date: string, token: string}>
     */
    public function preview(Carbon $startDate, Carbon $endDate, array $pattern, int $limit = 14): Collection
    {
        $pattern = array_values(array_filter(array_map('trim', $pattern), fn ($t) => $t !== ''));

        if ($pattern === []) {
            throw RosterException::emptyPattern();
        }

        $rows = collect();
        $dayIndex = 0;

        for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate) && $rows->count() < $limit; $date->addDay()) {
            $rows->push([
                'date' => $date->toDateString(),
                'token' => $pattern[$dayIndex % count($pattern)],
            ]);
            $dayIndex++;
        }

        return $rows;
    }
}
