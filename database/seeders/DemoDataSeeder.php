<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Services\GeofenceService;
use App\Services\RosterService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Optional demo data: assignments, a month of roster, some past attendance and
 * an open payroll period — enough for the dashboard and reports to show real
 * numbers straight after install.
 *
 * Attendance rows are written directly rather than through AttendanceService,
 * because the service (correctly) refuses to backdate a check-in.
 */
class DemoDataSeeder extends Seeder
{
    public function run(RosterService $rosters, GeofenceService $geofence): void
    {
        $employees = Employee::active()->orderBy('employee_code')->get();
        $locations = Location::active()->orderBy('location_code')->get();
        $shifts = Shift::active()->get()->keyBy('shift_code');

        if ($employees->isEmpty() || $locations->isEmpty() || $shifts->isEmpty()) {
            $this->command?->warn('DemoDataSeeder dilewati: master data belum lengkap.');

            return;
        }

        $periodStart = Carbon::today()->startOfMonth();
        $periodEnd = Carbon::today()->endOfMonth();

        $this->seedAssignments($employees, $locations, $shifts, $periodStart);
        $this->seedRosters($rosters, $employees, $locations, $periodStart, $periodEnd);
        $this->seedAttendance($geofence, $periodStart);
        $this->seedPayrollPeriod($periodStart, $periodEnd);
    }

    private function seedAssignments($employees, $locations, $shifts, Carbon $start): void
    {
        $shiftCodes = ['S1', 'S2', 'S3'];

        foreach ($employees as $index => $employee) {
            Assignment::updateOrCreate(
                ['employee_id' => $employee->id, 'start_date' => $start->toDateString()],
                [
                    'location_id' => $locations[$index % $locations->count()]->id,
                    'shift_id' => $shifts[$shiftCodes[$index % 3]]->id,
                    'end_date' => null,
                    'status' => Assignment::ACTIVE,
                ],
            );
        }
    }

    private function seedRosters(RosterService $rosters, $employees, $locations, Carbon $start, Carbon $end): void
    {
        $basePattern = ['S1', 'S2', 'S3', 'OFF'];

        // Split the workforce across locations. Each group starts the rotation
        // one step further along, so the whole workforce is never OFF on the
        // same day and every date has some coverage to look at.
        foreach ($locations as $index => $location) {
            $group = $employees->filter(fn ($e, $key) => $key % $locations->count() === $index);

            if ($group->isEmpty()) {
                continue;
            }

            $offset = $index % count($basePattern);
            $pattern = array_merge(
                array_slice($basePattern, $offset),
                array_slice($basePattern, 0, $offset),
            );

            $rosters->generate(
                employeeIds: $group->pluck('id')->all(),
                locationId: $location->id,
                startDate: $start,
                endDate: $end,
                pattern: $pattern,
                overwrite: false,
            );
        }
    }

    /**
     * Fills in attendance for every scheduled day already in the past, with a
     * realistic mix of on-time, late and missed days.
     */
    private function seedAttendance(GeofenceService $geofence, Carbon $start): void
    {
        // Only shifts that have already finished. A shift still in progress is
        // left open so the check-in screen has something real to act on.
        $rosters = ShiftRoster::with(['shift', 'location', 'employee'])
            ->scheduled()
            ->whereDate('roster_date', '>=', $start)
            ->where('end_datetime', '<=', Carbon::now())
            ->orderBy('roster_date')
            ->get();

        foreach ($rosters as $index => $roster) {
            // Every 9th scheduled day is a no-show, so reports have ABSENT rows.
            if ($index % 9 === 8) {
                continue;
            }

            $isLate = $index % 5 === 3;
            $tolerance = $roster->shift->late_tolerance_minutes;

            $checkIn = $roster->start_datetime->copy()
                ->addMinutes($isLate ? $tolerance + 7 + ($index % 11) : max(0, 3 - ($index % 4)));

            $checkOut = $roster->end_datetime->copy()->addMinutes($index % 6);

            // Nudge the coordinates a few metres off the pin so the stored
            // distance looks like a real reading rather than a perfect zero.
            $offsetMetres = 2 + ($index % 6);
            $latitude = round($roster->location->latitude + ($offsetMetres / 111320), 7);
            $longitude = $roster->location->longitude;

            $distance = round(
                $geofence->calculateDistance(
                    $latitude,
                    $longitude,
                    $roster->location->latitude,
                    $roster->location->longitude,
                ),
                2,
            );

            $lateMinutes = $isLate
                ? (int) $roster->start_datetime->diffInMinutes($checkIn)
                : 0;

            Attendance::updateOrCreate(
                ['employee_id' => $roster->employee_id, 'attendance_date' => $roster->roster_date->toDateString()],
                [
                    'roster_id' => $roster->id,
                    'location_id' => $roster->location_id,
                    'check_in_at' => $checkIn,
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_accuracy' => 5 + ($index % 9),
                    'check_in_distance' => $distance,
                    'check_out_at' => $checkOut,
                    'check_out_latitude' => $latitude,
                    'check_out_longitude' => $longitude,
                    'check_out_accuracy' => 5 + ($index % 7),
                    'check_out_distance' => $distance,
                    'late_minutes' => $lateMinutes,
                    'status' => $lateMinutes > 0 ? Attendance::LATE : Attendance::PRESENT,
                ],
            );
        }
    }

    private function seedPayrollPeriod(Carbon $start, Carbon $end): void
    {
        PayrollPeriod::updateOrCreate(
            ['period_code' => $start->format('Y-m')],
            [
                'period_name' => $start->translatedFormat('F Y'),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => PayrollPeriod::OPEN,
            ],
        );
    }
}
