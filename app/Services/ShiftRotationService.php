<?php

namespace App\Services;

use App\Exceptions\AssignmentException;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftRoster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rotating shift generator for the Assignment screen (spec §18).
 *
 * The rule HR actually works to: an employee keeps one shift for a whole cycle
 * — a week by default — and steps down to the next shift when the cycle turns.
 * Monday 1 Aug on shift 3 means Monday 8 Aug on shift 2, 15 Aug on shift 1,
 * then back to shift 3 on 22 Aug.
 *
 * Three things fall out of that and are the reason this is not just a loop:
 *
 *  - Where the rotation starts is not free. An employee already assigned to a
 *    shift must continue from it, and continue from the right point: generating
 *    a month that begins three cycles after their current assignment has to
 *    land on shift 3 - 3 steps, not on shift 3 again. See resolveStartIndex().
 *
 *  - An assignment is a date range, and one employee may not hold two
 *    overlapping active ones. Anything already covering the target range has to
 *    be trimmed or dropped first, which is what makes `replace` load-bearing
 *    rather than a convenience.
 *
 *  - Days off are per employee, not per team: the post still has to be manned
 *    on the day one guard is resting, so their off days must land on different
 *    dates. See offOffsets().
 *
 * Assignments are the contract ("this person works this shift here, these
 * dates"); shift_rosters are the day-by-day records attendance actually checks
 * against. Generating the first without the second produces a schedule nobody
 * can clock in to, so both are written in one transaction — and a day off is
 * a roster row with no shift, which is why the assignment block still spans the
 * whole cycle even when two of its days are rest days.
 */
class ShiftRotationService
{
    /** Shift number counts down each cycle: 3 → 2 → 1 → 3 (spec: "turun shift"). */
    public const DOWN = 'DOWN';

    /** The mirror image, for sites that rotate the other way: 1 → 2 → 3 → 1. */
    public const UP = 'UP';

    /** Rest days are spread across the team so no date loses every employee. */
    public const OFF_AUTO = 'AUTO';

    /** Rest days fall on the same weekday(s) for everyone in the batch. */
    public const OFF_FIXED = 'FIXED';

    /**
     * Builds the rotation without writing anything. The generate dialog renders
     * this as its preview, and generate() runs on exactly the same result, so
     * what HR approves is what gets stored.
     *
     * @param  array<int, int>  $employeeIds
     * @param  array<int, int>  $fixedOffWeekdays  ISO weekdays, 1 = Monday
     * @param  array<int, int>|null  $shiftIds  the shifts to cycle through; null = all active
     * @return array<string, mixed>
     */
    public function plan(
        array $employeeIds,
        int $locationId,
        Carbon $startDate,
        Carbon $endDate,
        int $cycleDays = 7,
        string $direction = self::DOWN,
        ?int $startShiftId = null,
        bool $replace = true,
        int $offDaysPerCycle = 1,
        string $offDayMode = self::OFF_AUTO,
        array $fixedOffWeekdays = [],
        ?array $shiftIds = null,
    ): array {
        $location = Location::findOrFail($locationId);

        if (! $location->isActive()) {
            throw AssignmentException::inactiveLocation();
        }

        $shifts = $this->rotationShifts($shiftIds);
        $employees = $this->employees($employeeIds);

        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();

        if ($endDate->lessThan($startDate)) {
            $endDate = $startDate->copy();
        }

        // A cycle that is all rest days would schedule nobody, so at least one
        // working day per cycle is kept back.
        $offDaysPerCycle = max(0, min($offDaysPerCycle, $cycleDays - 1));

        $rows = $employees->values()->map(function (Employee $employee, int $position) use (
            $shifts, $startDate, $endDate, $cycleDays, $direction, $startShiftId, $replace,
            $offDaysPerCycle, $offDayMode, $fixedOffWeekdays
        ) {
            $anchor = $this->anchorFor($employee, $startDate);
            $startIndex = $this->resolveStartIndex(
                $shifts, $anchor, $startDate, $cycleDays, $direction, $startShiftId, $position
            );

            $conflicts = $this->conflictsFor($employee, $startDate, $endDate);

            // Without `replace` a clash is fatal for this employee only: the
            // rest of the batch still generates, and the reason is reported so
            // HR can see who was left out rather than silently losing them.
            $blocked = $conflicts->isNotEmpty() && ! $replace;

            $offOffsets = $this->offOffsets($position, $cycleDays, $offDaysPerCycle, $offDayMode);

            $blocks = $blocked ? [] : $this->blocks(
                $shifts, $startIndex, $startDate, $endDate, $cycleDays, $direction,
                $offOffsets, $offDayMode, $fixedOffWeekdays,
            );

            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->full_name,
                'anchor_shift_code' => $anchor?->shift?->shift_code,
                'anchor_start_date' => $anchor?->start_date->toDateString(),
                'off_label' => $this->offLabel($blocks, $offDaysPerCycle),
                'blocks' => $blocks,
                'work_days' => collect($blocks)->sum('work_days'),
                'off_days' => collect($blocks)->sum(fn ($b) => count($b['off_dates'])),
                'conflicts' => $conflicts->map(fn (Assignment $a) => [
                    'id' => $a->id,
                    'shift_code' => $a->shift->shift_code,
                    'start_date' => $a->start_date->toDateString(),
                    'end_date' => $a->end_date?->toDateString(),
                    // What clearConflicts() will do to it, spelled out for the preview.
                    'action' => $a->start_date->lessThan($startDate) ? 'TRIM' : 'DELETE',
                ])->values()->all(),
                'skipped' => $blocked,
                'reason' => $blocked ? 'Bentrok dengan assignment aktif yang sudah ada.' : null,
            ];
        })->all();

        return [
            'cycle_days' => $cycleDays,
            'direction' => $direction,
            'off_days_per_cycle' => $offDaysPerCycle,
            'off_day_mode' => $offDayMode,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'location_id' => $location->id,
            'location_name' => $location->location_name,
            'rotation' => $shifts->map(fn (Shift $s) => $s->shift_code)->all(),
            'employees' => $rows,
        ];
    }

    /**
     * Commits a plan: assignment rows per cycle, plus the daily shift record
     * per employee behind them.
     *
     * @param  array<int, int>  $employeeIds
     * @param  array<int, int>  $fixedOffWeekdays
     * @param  array<int, int>|null  $shiftIds
     * @return array<string, mixed>
     */
    public function generate(
        array $employeeIds,
        int $locationId,
        Carbon $startDate,
        Carbon $endDate,
        int $cycleDays = 7,
        string $direction = self::DOWN,
        ?int $startShiftId = null,
        bool $replace = true,
        int $offDaysPerCycle = 1,
        string $offDayMode = self::OFF_AUTO,
        array $fixedOffWeekdays = [],
        ?array $shiftIds = null,
        bool $withRoster = true,
    ): array {
        $plan = $this->plan(
            $employeeIds, $locationId, $startDate, $endDate, $cycleDays, $direction,
            $startShiftId, $replace, $offDaysPerCycle, $offDayMode, $fixedOffWeekdays, $shiftIds,
        );

        $location = Location::findOrFail($locationId);
        $shifts = $this->rotationShifts($shiftIds)->keyBy('id');

        $assignmentsCreated = 0;
        $assignmentsReplaced = 0;
        $rostersCreated = 0;
        $rostersUpdated = 0;
        $rostersSkipped = 0;
        $offCreated = 0;
        $employeesDone = 0;
        $messages = [];

        DB::transaction(function () use (
            &$plan, $location, $shifts, $withRoster,
            &$assignmentsCreated, &$assignmentsReplaced, &$rostersCreated,
            &$rostersUpdated, &$rostersSkipped, &$offCreated, &$employeesDone, &$messages
        ) {
            $rangeStart = Carbon::parse($plan['start_date']);

            foreach ($plan['employees'] as &$row) {
                if ($row['skipped'] || $row['blocks'] === []) {
                    $messages[] = sprintf(
                        '%s dilewati: %s',
                        $row['employee_code'],
                        $row['reason'] ?? 'tidak ada jadwal yang dihasilkan.',
                    );

                    continue;
                }

                $assignmentsReplaced += $this->clearConflicts($row, $rangeStart);

                foreach ($row['blocks'] as &$block) {
                    $assignment = Assignment::create([
                        'employee_id' => $row['employee_id'],
                        'location_id' => $location->id,
                        'shift_id' => $block['shift_id'],
                        'start_date' => $block['start_date'],
                        'end_date' => $block['end_date'],
                        'status' => Assignment::ACTIVE,
                    ]);

                    $block['assignment_id'] = $assignment->id;
                    $assignmentsCreated++;

                    if (! $withRoster) {
                        continue;
                    }

                    $counts = $this->writeRoster(
                        $row['employee_id'],
                        $location->id,
                        $shifts->get($block['shift_id']),
                        Carbon::parse($block['start_date']),
                        Carbon::parse($block['end_date']),
                        $block['off_dates'],
                    );

                    $rostersCreated += $counts['created'];
                    $rostersUpdated += $counts['updated'];
                    $rostersSkipped += $counts['skipped'];
                    $offCreated += $counts['off'];
                }
                unset($block);

                $employeesDone++;
            }
            unset($row);
        });

        if ($rostersSkipped > 0) {
            $messages[] = sprintf(
                '%d hari tidak diubah di Shift Roster karena sudah memiliki absensi.',
                $rostersSkipped,
            );
        }

        AuditLog::record('assignment.rotation', $location, sprintf(
            'Generate rotasi shift %s s/d %s untuk %d karyawan (siklus %d hari, arah %s, libur %d hari/siklus)',
            $plan['start_date'],
            $plan['end_date'],
            $employeesDone,
            $cycleDays,
            $direction,
            $plan['off_days_per_cycle'],
        ), [
            'assignments_created' => $assignmentsCreated,
            'assignments_replaced' => $assignmentsReplaced,
            'rosters_created' => $rostersCreated,
            'rosters_updated' => $rostersUpdated,
            'off_days' => $offCreated,
        ]);

        return $plan + [
            'assignments_created' => $assignmentsCreated,
            'assignments_replaced' => $assignmentsReplaced,
            'rosters_created' => $rostersCreated,
            'rosters_updated' => $rostersUpdated,
            'rosters_skipped' => $rostersSkipped,
            'off_created' => $offCreated,
            'employees_done' => $employeesDone,
            'messages' => $messages,
        ];
    }

    /* ── Rotation maths ─────────────────────────────────────────────── */

    /**
     * The shifts a rotation cycles through, in shift order.
     *
     * Ordered by start_time rather than by code so "shift 1" means the earliest
     * one whatever it happens to be called — and so the cross-day night shift
     * sorts last, where it belongs, instead of wherever its code falls.
     *
     * A selection narrows the cycle without touching the master data, which is
     * what lets a site run, say, a two-shift 12-hour rotation while the old
     * 8-hour shifts stay active for the history that already references them.
     * No selection means every active shift.
     *
     * @param  array<int, int>|null  $shiftIds
     * @return Collection<int, Shift>
     */
    private function rotationShifts(?array $shiftIds = null): Collection
    {
        $selected = array_values(array_filter(array_map('intval', $shiftIds ?? [])));

        $shifts = Shift::active()
            ->when($selected !== [], fn ($q) => $q->whereIn('id', $selected))
            ->orderBy('start_time')
            ->orderBy('shift_code')
            ->get();

        if ($shifts->count() < 2) {
            // Which shortage it is decides where the fix lives, so the two are
            // reported separately rather than sharing one vague message.
            throw $selected !== []
                ? AssignmentException::notEnoughSelectedShifts()
                : AssignmentException::notEnoughShifts();
        }

        return $shifts->values();
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @return Collection<int, Employee>
     */
    private function employees(array $employeeIds): Collection
    {
        $employees = Employee::active()
            ->whereIn('id', $employeeIds)
            ->orderBy('employee_code')
            ->get();

        if ($employees->isEmpty()) {
            throw AssignmentException::noEmployees();
        }

        return $employees;
    }

    /**
     * The assignment the rotation continues from: the latest active one that
     * had already started by the generation date. A later-starting assignment
     * is deliberately ignored — it describes a future the rotation is about to
     * overwrite, not the shift the employee is on now.
     */
    private function anchorFor(Employee $employee, Carbon $startDate): ?Assignment
    {
        return Assignment::with('shift')
            ->where('employee_id', $employee->id)
            ->where('status', Assignment::ACTIVE)
            ->whereDate('start_date', '<=', $startDate->toDateString())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Which shift the first generated cycle lands on.
     *
     * The anchor gives both a shift and the date it started, and the rotation
     * has been turning ever since — so the answer is the anchor's shift stepped
     * once per whole cycle elapsed. Generating from the very day the anchor
     * started reproduces the anchor's own shift (nothing has turned yet);
     * generating a week later steps down once, three weeks later three times.
     *
     * With no anchor to continue from, an explicit choice wins; failing that
     * employees are staggered across the shifts by position, so a first-time
     * generate still leaves every shift covered instead of putting the whole
     * team on shift 1.
     *
     * @param  Collection<int, Shift>  $shifts
     */
    private function resolveStartIndex(
        Collection $shifts,
        ?Assignment $anchor,
        Carbon $startDate,
        int $cycleDays,
        string $direction,
        ?int $startShiftId,
        int $position,
    ): int {
        $anchorIndex = $anchor
            ? $shifts->search(fn (Shift $s) => $s->id === $anchor->shift_id)
            : false;

        if ($anchorIndex !== false) {
            $elapsedDays = (int) $anchor->start_date->copy()->startOfDay()->diffInDays($startDate, false);
            $steps = (int) floor($elapsedDays / max(1, $cycleDays));

            return $this->step($anchorIndex, $steps, $direction, $shifts->count());
        }

        if ($startShiftId !== null) {
            $chosen = $shifts->search(fn (Shift $s) => $s->id === $startShiftId);

            if ($chosen !== false) {
                return $chosen;
            }
        }

        return $position % $shifts->count();
    }

    /** Moves `$steps` cycles along the rotation, wrapping in either direction. */
    private function step(int $index, int $steps, string $direction, int $count): int
    {
        $moved = $direction === self::UP ? $index + $steps : $index - $steps;

        return (($moved % $count) + $count) % $count;
    }

    /**
     * Which days of a cycle this employee rests on, as offsets from the cycle's
     * first day.
     *
     * The offsets are derived from the employee's position in the batch, so
     * consecutive employees rest on consecutive days and the post keeps its
     * cover. Two rest days are put half a cycle apart rather than back to back,
     * which both spreads the absence and gives the employee a break in each
     * half of the week.
     *
     * FIXED mode returns nothing: there the rest days come from the chosen
     * weekdays, which are the same for everyone and so cannot be derived from
     * position.
     *
     * @return array<int, int>
     */
    private function offOffsets(int $position, int $cycleDays, int $offDaysPerCycle, string $offDayMode): array
    {
        if ($offDaysPerCycle < 1 || $offDayMode === self::OFF_FIXED) {
            return [];
        }

        $first = $position % $cycleDays;
        $offsets = [$first];

        for ($n = 1; $n < $offDaysPerCycle; $n++) {
            $spread = (int) round($cycleDays * $n / $offDaysPerCycle);
            $candidate = ($first + $spread) % $cycleDays;

            // A collision would silently cost the employee a rest day, so walk
            // forward until a free offset is found.
            while (in_array($candidate, $offsets, true)) {
                $candidate = ($candidate + 1) % $cycleDays;
            }

            $offsets[] = $candidate;
        }

        sort($offsets);

        return $offsets;
    }

    /**
     * Slices the range into cycles, one shift each. The final cycle is clipped
     * to end_date, so a range that does not divide evenly ends on a short block
     * rather than running past what HR asked for.
     *
     * @param  Collection<int, Shift>  $shifts
     * @param  array<int, int>  $offOffsets
     * @param  array<int, int>  $fixedOffWeekdays
     * @return array<int, array<string, mixed>>
     */
    private function blocks(
        Collection $shifts,
        int $startIndex,
        Carbon $startDate,
        Carbon $endDate,
        int $cycleDays,
        string $direction,
        array $offOffsets,
        string $offDayMode,
        array $fixedOffWeekdays,
    ): array {
        $blocks = [];
        $index = $startIndex;
        $cursor = $startDate->copy();

        while ($cursor->lessThanOrEqualTo($endDate)) {
            $blockEnd = $cursor->copy()->addDays($cycleDays - 1);

            if ($blockEnd->greaterThan($endDate)) {
                $blockEnd = $endDate->copy();
            }

            /** @var Shift $shift */
            $shift = $shifts[$index];

            $days = (int) $cursor->diffInDays($blockEnd) + 1;
            $offDates = $this->offDatesFor($cursor, $blockEnd, $offOffsets, $offDayMode, $fixedOffWeekdays);

            $blocks[] = [
                'shift_id' => $shift->id,
                'shift_code' => $shift->shift_code,
                'shift_name' => $shift->shift_name,
                'start_date' => $cursor->toDateString(),
                'end_date' => $blockEnd->toDateString(),
                'days' => $days,
                'work_days' => $days - count($offDates),
                'off_dates' => $offDates,
                'weekday' => $cursor->translatedFormat('D'),
            ];

            $cursor = $blockEnd->copy()->addDay();
            $index = $this->step($index, 1, $direction, $shifts->count());
        }

        return $blocks;
    }

    /**
     * The rest days inside one cycle, as date strings.
     *
     * @param  array<int, int>  $offOffsets
     * @param  array<int, int>  $fixedOffWeekdays
     * @return array<int, string>
     */
    private function offDatesFor(
        Carbon $blockStart,
        Carbon $blockEnd,
        array $offOffsets,
        string $offDayMode,
        array $fixedOffWeekdays,
    ): array {
        if ($offDayMode === self::OFF_FIXED) {
            $dates = [];

            for ($date = $blockStart->copy(); $date->lessThanOrEqualTo($blockEnd); $date->addDay()) {
                if (in_array($date->isoWeekday(), $fixedOffWeekdays, true)) {
                    $dates[] = $date->toDateString();
                }
            }

            return $dates;
        }

        $dates = [];

        foreach ($offOffsets as $offset) {
            $date = $blockStart->copy()->addDays($offset);

            // A short final cycle can fall before a late offset.
            if ($date->lessThanOrEqualTo($blockEnd)) {
                $dates[] = $date->toDateString();
            }
        }

        sort($dates);

        return $dates;
    }

    /**
     * Human-readable summary of when this employee rests, for the preview
     * table — the weekdays of the first cycle's rest days.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function offLabel(array $blocks, int $offDaysPerCycle): string
    {
        if ($offDaysPerCycle < 1 || $blocks === []) {
            return 'Tanpa libur';
        }

        $dates = $blocks[0]['off_dates'] ?? [];

        if ($dates === []) {
            return 'Tanpa libur';
        }

        return collect($dates)
            ->map(fn (string $date) => Carbon::parse($date)->translatedFormat('D'))
            ->implode(', ');
    }

    /* ── Existing assignments ───────────────────────────────────────── */

    /**
     * @return Collection<int, Assignment>
     */
    private function conflictsFor(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        return Assignment::with('shift')
            ->where('employee_id', $employee->id)
            ->where('status', Assignment::ACTIVE)
            ->overlapping($startDate->toDateString(), $endDate->toDateString())
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Makes room for the new rotation.
     *
     * An assignment that started before the range is history worth keeping, so
     * it is closed off the day before the rotation takes over. One that starts
     * inside the range is entirely superseded and goes.
     *
     * @param  array<string, mixed>  $row
     */
    private function clearConflicts(array $row, Carbon $startDate): int
    {
        $handled = 0;

        foreach ($row['conflicts'] as $conflict) {
            $assignment = Assignment::find($conflict['id']);

            if (! $assignment) {
                continue;
            }

            if ($assignment->start_date->lessThan($startDate)) {
                $assignment->update(['end_date' => $startDate->copy()->subDay()->toDateString()]);
            } else {
                $assignment->delete();
            }

            $handled++;
        }

        return $handled;
    }

    /* ── Daily records ──────────────────────────────────────────────── */

    /**
     * The per-employee, per-date shift record. Attendance resolves check-in
     * against these rows, so an assignment without them is unusable.
     *
     * A rest day is written as a row with no shift — present, so the calendar
     * shows an explicit OFF rather than a gap, but with nothing to clock in to.
     *
     * A day already clocked in on is left exactly as it was: rewriting it would
     * detach the attendance from the shift it was measured against.
     *
     * @param  array<int, string>  $offDates
     * @return array{created: int, updated: int, skipped: int, off: int}
     */
    private function writeRoster(
        int $employeeId,
        int $locationId,
        ?Shift $shift,
        Carbon $start,
        Carbon $end,
        array $offDates,
    ): array {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $off = 0;

        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $isOff = in_array($date->toDateString(), $offDates, true);
            $dayShift = $isOff ? null : $shift;

            $row = [
                'employee_id' => $employeeId,
                'location_id' => $locationId,
                'shift_id' => $dayShift?->id,
                'roster_date' => $date->toDateString(),
                'start_datetime' => $dayShift?->startDatetimeFor($date),
                'end_datetime' => $dayShift?->endDatetimeFor($date),
                'status' => $dayShift ? ShiftRoster::SCHEDULED : ShiftRoster::OFF,
            ];

            $existing = ShiftRoster::with('attendance')
                ->where('employee_id', $employeeId)
                ->whereDate('roster_date', $date->toDateString())
                ->first();

            if (! $existing) {
                ShiftRoster::create($row);
                $created++;
                $off += $isOff ? 1 : 0;

                continue;
            }

            if ($existing->attendance) {
                $skipped++;

                continue;
            }

            $existing->update($row);
            $updated++;
            $off += $isOff ? 1 : 0;
        }

        return compact('created', 'updated', 'skipped', 'off');
    }
}
