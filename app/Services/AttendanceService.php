<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ShiftRoster;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Spec §27, §28, §53 — the server-side check-in / check-out pipeline.
 *
 * Order matters here and mirrors the acceptance criteria: the employee comes
 * from the session (AC-002), the roster and location come from the database
 * (AC-003), the distance is recalculated (AC-006), the timestamp is the
 * server's (AC-011), and only then is anything written.
 */
class AttendanceService
{
    public function __construct(
        private readonly GeofenceService $geofence,
        private readonly AttendancePhotoService $photos,
    ) {}

    /**
     * What the check-in screen needs to render before the employee acts:
     * which shift they are on and which location to draw on the map.
     *
     * @return array{roster: ?ShiftRoster, attendance: ?Attendance, state: string}
     */
    public function context(Employee $employee, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        $open = $this->openAttendance($employee);

        if ($open) {
            return ['roster' => $open->roster, 'attendance' => $open, 'state' => 'AWAITING_CHECK_OUT'];
        }

        $candidates = $this->rosterCandidates($employee, $now);
        $available = $candidates->first(fn (ShiftRoster $r) => ! $this->hasAttendance($r));

        if ($available) {
            return ['roster' => $available, 'attendance' => null, 'state' => 'AWAITING_CHECK_IN'];
        }

        if ($candidates->isNotEmpty()) {
            return ['roster' => $candidates->first(), 'attendance' => $candidates->first()->attendance, 'state' => 'DONE'];
        }

        return ['roster' => null, 'attendance' => null, 'state' => 'NO_ROSTER'];
    }

    /**
     * Spec §27. Throws AttendanceException for every business refusal; only
     * returns once the row is committed.
     */
    public function checkIn(
        Employee $employee,
        float $latitude,
        float $longitude,
        float $accuracy,
        UploadedFile $photo,
        ?Carbon $now = null,
    ): Attendance {
        $now ??= Carbon::now();

        $this->assertEmployeeActive($employee);

        // An employee who never checked out cannot start another shift.
        if ($this->openAttendance($employee)) {
            throw new AttendanceException(
                'Anda masih memiliki check-in yang belum di-check-out.',
                'OPEN_ATTENDANCE_EXISTS',
            );
        }

        $roster = $this->resolveRosterForCheckIn($employee, $now);
        $location = $roster->location;

        $verdict = $this->geofence->validate($latitude, $longitude, $accuracy, $location);

        if (! $verdict['valid']) {
            throw new AttendanceException($verdict['message'], $verdict['code'], $verdict);
        }

        $lateMinutes = $this->lateMinutes($roster, $now);

        try {
            return DB::transaction(function () use (
                $employee, $roster, $location, $latitude, $longitude, $accuracy, $verdict, $photo, $now, $lateMinutes
            ) {
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'roster_id' => $roster->id,
                    'location_id' => $location->id,
                    'attendance_date' => $roster->roster_date,
                    // AC-011: server clock, never the browser's.
                    'check_in_at' => $now,
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_accuracy' => $verdict['accuracy'],
                    'check_in_distance' => $verdict['distance'],
                    'late_minutes' => $lateMinutes,
                    // Becomes PRESENT or LATE at check-out; until then the day
                    // is incomplete and payroll will not count it (spec §39).
                    'status' => Attendance::INCOMPLETE,
                ]);

                $stored = $this->photos->store($attendance, $photo, AttendancePhoto::CHECK_IN);
                $attendance->update(['check_in_photo' => $stored->file_path]);

                AuditLog::record('attendance.check_in', $attendance, sprintf(
                    'Check-in %s di %s (jarak %.2f m, akurasi %.2f m)',
                    $employee->employee_code,
                    $location->location_name,
                    $verdict['distance'],
                    $verdict['accuracy'],
                ), $verdict);

                return $attendance->fresh(['roster.shift', 'location', 'photos']);
            });
        } catch (QueryException $e) {
            // AC-012: the unique (employee_id, attendance_date) index is the
            // real duplicate guard — it also catches two taps racing each other.
            if ($this->isUniqueViolation($e)) {
                throw AttendanceException::duplicateCheckIn();
            }

            throw $e;
        }
    }

    /**
     * Spec §28. Re-runs the full geofence check — being inside the radius at
     * check-in does not license checking out from anywhere.
     */
    public function checkOut(
        Employee $employee,
        float $latitude,
        float $longitude,
        float $accuracy,
        UploadedFile $photo,
        ?Carbon $now = null,
    ): Attendance {
        $now ??= Carbon::now();

        $this->assertEmployeeActive($employee);

        $attendance = $this->openAttendance($employee);

        if (! $attendance) {
            throw AttendanceException::noOpenAttendance();
        }

        if ($now->greaterThan($this->checkOutDeadline($attendance))) {
            throw AttendanceException::checkOutWindowExpired();
        }

        $verdict = $this->geofence->validate($latitude, $longitude, $accuracy, $attendance->location);

        if (! $verdict['valid']) {
            throw new AttendanceException($verdict['message'], $verdict['code'], $verdict);
        }

        return DB::transaction(function () use ($attendance, $latitude, $longitude, $verdict, $photo, $now) {
            $attendance->update([
                'check_out_at' => $now,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_accuracy' => $verdict['accuracy'],
                'check_out_distance' => $verdict['distance'],
                'status' => $attendance->late_minutes > 0 ? Attendance::LATE : Attendance::PRESENT,
            ]);

            $stored = $this->photos->store($attendance, $photo, AttendancePhoto::CHECK_OUT);
            $attendance->update(['check_out_photo' => $stored->file_path]);

            AuditLog::record('attendance.check_out', $attendance, sprintf(
                'Check-out %s (jarak %.2f m, akurasi %.2f m)',
                $attendance->employee->employee_code,
                $verdict['distance'],
                $verdict['accuracy'],
            ), $verdict);

            return $attendance->fresh(['roster.shift', 'location', 'photos']);
        });
    }

    /**
     * Spec §34 — minutes past (shift start + tolerance). Zero when on time.
     */
    public function lateMinutes(ShiftRoster $roster, Carbon $checkInAt): int
    {
        $shift = $roster->shift;

        if (! $shift || ! $roster->start_datetime) {
            return 0;
        }

        $tolerance = $shift->late_tolerance_minutes ?? config('hris.default_late_tolerance_minutes');
        $threshold = $roster->start_datetime->copy()->addMinutes($tolerance);

        if ($checkInAt->lessThanOrEqualTo($threshold)) {
            return 0;
        }

        // Reported against the scheduled start, not against the threshold, so
        // "terlambat 20 menit" means what a supervisor expects it to mean.
        return (int) $roster->start_datetime->diffInMinutes($checkInAt);
    }

    /**
     * The attendance row this employee still owes a check-out on.
     */
    public function openAttendance(Employee $employee): ?Attendance
    {
        return Attendance::with(['roster.shift', 'location', 'employee'])
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->orderByDesc('check_in_at')
            ->first();
    }

    private function resolveRosterForCheckIn(Employee $employee, Carbon $now): ShiftRoster
    {
        $candidates = $this->rosterCandidates($employee, $now);

        if ($candidates->isEmpty()) {
            throw AttendanceException::noRoster();
        }

        $available = $candidates->first(fn (ShiftRoster $roster) => ! $this->hasAttendance($roster));

        // Every shift in range has already been attended — that is a duplicate
        // attempt, not a missing schedule, and the message should say so.
        return $available ?? throw AttendanceException::duplicateCheckIn();
    }

    /**
     * Rosters whose check-in window is open right now, earliest first.
     *
     * The window opens `checkin_early_window_minutes` before the shift starts
     * and closes when the shift ends. Because start/end are stored as absolute
     * datetimes (spec §17), the cross-day shift 3 is matched by the same query
     * as any other — no special-casing on roster_date.
     *
     * @return \Illuminate\Support\Collection<int, ShiftRoster>
     */
    private function rosterCandidates(Employee $employee, Carbon $now)
    {
        $earlyWindow = (int) config('hris.checkin_early_window_minutes');

        // "now >= start - window" is rearranged to "start <= now + window" so
        // the comparison is against a plain column: it uses the index on
        // start_datetime and needs no vendor-specific date arithmetic.
        $opensBy = $now->copy()->addMinutes($earlyWindow);

        return ShiftRoster::with(['shift', 'location', 'attendance'])
            ->scheduled()
            ->where('employee_id', $employee->id)
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('start_datetime', '<=', $opensBy)
            ->where('end_datetime', '>=', $now)
            ->orderBy('start_datetime')
            ->get();
    }

    private function hasAttendance(ShiftRoster $roster): bool
    {
        return $roster->attendance !== null;
    }

    /**
     * How long after the shift ends a check-out is still accepted. Without this
     * an abandoned check-in from last week could be closed today and silently
     * become a paid working day.
     */
    private function checkOutDeadline(Attendance $attendance): Carbon
    {
        $grace = (int) config('hris.checkout_grace_minutes');

        $end = $attendance->roster?->end_datetime
            ?? $attendance->check_in_at->copy()->addDay();

        return $end->copy()->addMinutes($grace);
    }

    private function assertEmployeeActive(Employee $employee): void
    {
        if (! $employee->isActive()) {
            throw AttendanceException::employeeInactive();
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return in_array((string) $e->errorInfo[1], ['1062'], true)
            || $e->getCode() === '23000';
    }
}
