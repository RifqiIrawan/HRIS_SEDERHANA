<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\AssignmentRotationRequest;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ShiftRoster;
use App\Services\ShiftRotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Spec §18. */
class AssignmentController extends Controller
{
    /** A detail window wider than a quarter stops being a detail. */
    private const MAX_DETAIL_DAYS = 92;

    public function __construct(private readonly ShiftRotationService $rotation) {}

    /**
     * One row per employee rather than one per assignment.
     *
     * A generated rotation produces four or five assignment rows for the same
     * person — the same fact stated once per cycle — which buries a six-person
     * team under thirty lines. The listing collapses them: the shift shown is
     * the one the rotation opens on, and the dates span the whole run. The
     * per-cycle rows are still there and still reachable, through Detail Shift.
     */
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('assignments.index');
        }

        // Applied twice on purpose: once to decide which employees appear, and
        // once to the rows being summarised — so a filtered list describes the
        // filtered assignments rather than quietly summarising all of them.
        $filters = fn ($query) => $query
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('shift_id'), fn ($q) => $q->where('shift_id', $request->integer('shift_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        $employees = Employee::query()
            ->whereHas('assignments', $filters)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->with(['assignments' => fn ($q) => $filters($q)
                ->with(['shift:id,shift_code,shift_name', 'location:id,location_name'])
                ->orderBy('start_date')
                ->orderBy('id')])
            ->tap(fn ($q) => $this->applyRotationSort($q, $request, $filters))
            ->paginate($this->perPage($request));

        return $this->paginated($employees, fn (Employee $e) => $this->transformRotation($e));
    }

    /**
     * Sorting by a span means sorting by an aggregate of rows on another table,
     * which a plain orderBy cannot reach — hence the correlated subqueries. The
     * default stays employee_code, the only column that reads as an order at
     * all when every row is a person.
     */
    private function applyRotationSort(mixed $query, Request $request, callable $filters): void
    {
        $direction = strtolower((string) $request->string('dir')) === 'desc' ? 'desc' : 'asc';

        $span = fn (string $aggregate) => $filters(
            Assignment::query()
                ->selectRaw($aggregate)
                ->whereColumn('assignments.employee_id', 'employees.id')
        );

        match ((string) $request->string('sort')) {
            'start_date' => $query->orderBy($span('MIN(start_date)'), $direction),
            'end_date' => $query->orderBy($span('MAX(end_date)'), $direction),
            'employee_name' => $query->orderBy('full_name', $direction),
            default => $query->orderBy('employee_code'),
        };
    }

    /**
     * Collapses one employee's assignments into the single row the table draws.
     *
     * @return array<string, mixed>
     */
    private function transformRotation(Employee $employee): array
    {
        $assignments = $employee->assignments;
        $first = $assignments->first();

        $locations = $assignments->pluck('location.location_name')->unique()->values();
        $shiftCodes = $assignments->pluck('shift.shift_code')->values();

        return [
            'id' => $first->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            // A rotation normally sits at one post; say so plainly when it does
            // not, rather than picking one of them and looking authoritative.
            'location_name' => $locations->count() === 1
                ? $locations->first()
                : $locations->count().' lokasi',
            'shift_id' => $first->shift_id,
            'shift_code' => $first->shift->shift_code,
            'shift_name' => $first->shift->shift_name,
            'rotation' => $shiftCodes->unique()->values()->all(),
            'cycles' => $assignments->count(),
            'start_date' => $first->start_date->toDateString(),
            'end_date' => $this->rotationEndDate($assignments),
            // One live cycle is enough to call the whole rotation live.
            'status' => $assignments->contains(fn (Assignment $a) => $a->status === Assignment::ACTIVE)
                ? Assignment::ACTIVE
                : Assignment::INACTIVE,
        ];
    }

    /**
     * The last date the rotation covers, or null when any cycle is open-ended —
     * an open cycle has no end, so neither does the run containing it.
     *
     * @param  Collection<int, Assignment>  $assignments
     */
    private function rotationEndDate($assignments): ?string
    {
        $latest = null;

        foreach ($assignments as $assignment) {
            if ($assignment->end_date === null) {
                return null;
            }

            if (! $latest || $assignment->end_date->greaterThan($latest)) {
                $latest = $assignment->end_date;
            }
        }

        return $latest?->toDateString();
    }

    /**
     * Deletes every assignment behind one summary row, and the daily records
     * generated alongside them.
     *
     * The row action has to match what the row means: it stands for the whole
     * rotation, so removing "it" removes the cycles it is made of. Deleting
     * only the first would leave the rest behind under a row that no longer
     * describes them.
     *
     * A day already clocked in on is the one thing kept. attendances.roster_id
     * is nullOnDelete, so removing such a roster would not fail — it would
     * quietly cut the attendance loose from the shift it was measured against,
     * leaving a record nobody can audit. Those days are counted and reported
     * instead.
     */
    public function destroyEmployeeAssignments(Employee $employee): JsonResponse
    {
        $rosters = ShiftRoster::where('employee_id', $employee->id)
            ->withCount('attendance')
            ->get();

        $removable = $rosters->where('attendance_count', 0);
        $kept = $rosters->count() - $removable->count();

        $deleted = DB::transaction(function () use ($employee, $removable) {
            $assignments = Assignment::where('employee_id', $employee->id)->delete();
            $days = $removable->isEmpty()
                ? 0
                : ShiftRoster::whereIn('id', $removable->pluck('id'))->delete();

            return ['assignments' => $assignments, 'rosters' => $days];
        });

        AuditLog::record('assignment.rotation_deleted', $employee, sprintf(
            '%d assignment dan %d jadwal harian %s dihapus',
            $deleted['assignments'],
            $deleted['rosters'],
            $employee->employee_code,
        ), $deleted + ['kept_with_attendance' => $kept]);

        $message = sprintf(
            '%d assignment dan %d jadwal harian %s berhasil dihapus.',
            $deleted['assignments'],
            $deleted['rosters'],
            $employee->employee_code,
        );

        if ($kept > 0) {
            $message .= sprintf(' %d hari dipertahankan karena sudah memiliki absensi.', $kept);
        }

        return $this->ok($deleted + ['kept_with_attendance' => $kept], $message);
    }

    public function store(AssignmentRequest $request): JsonResponse
    {
        $assignment = Assignment::create($request->validated())
            ->load(['employee', 'location', 'shift']);

        AuditLog::record('assignment.created', $assignment, sprintf(
            'Assignment %s → %s (%s)',
            $assignment->employee->employee_code,
            $assignment->location->location_name,
            $assignment->shift->shift_code,
        ));

        return $this->ok($this->transform($assignment), 'Assignment berhasil disimpan.', 201);
    }

    public function show(Assignment $assignment): JsonResponse
    {
        return $this->ok($this->transform($assignment->load(['employee', 'location', 'shift'])));
    }

    public function update(AssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment->update($request->validated());
        $assignment->load(['employee', 'location', 'shift']);

        AuditLog::record('assignment.updated', $assignment, 'Assignment diperbarui');

        return $this->ok($this->transform($assignment), 'Assignment berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $assignment->delete();

        AuditLog::record('assignment.deleted', null, 'Assignment #'.$assignment->id.' dihapus');

        return $this->ok(message: 'Assignment berhasil dihapus.');
    }

    /**
     * Dry run of the rotating-shift generator: the same plan generateRotation()
     * would commit, returned without writing anything so HR can check who lands
     * on which shift — and which existing assignments would be moved aside —
     * before any rows exist.
     */
    public function rotationPreview(AssignmentRotationRequest $request): JsonResponse
    {
        return $this->ok($this->rotation->plan(...$this->rotationArguments($request)));
    }

    /**
     * Generates the rotation: one assignment per cycle per employee, plus the
     * daily shift record each of those cycles is made of.
     */
    public function generateRotation(AssignmentRotationRequest $request): JsonResponse
    {
        $result = $this->rotation->generate(
            ...$this->rotationArguments($request),
            withRoster: $request->boolean('with_roster'),
        );

        return $this->ok($result, sprintf(
            '%d assignment dibuat untuk %d karyawan, %d jadwal harian tersimpan.',
            $result['assignments_created'],
            $result['employees_done'],
            $result['rosters_created'] + $result['rosters_updated'],
        ));
    }

    /**
     * The generator's arguments, resolved once so the preview and the commit
     * can never disagree about what was asked for.
     *
     * @return array<string, mixed>
     */
    private function rotationArguments(AssignmentRotationRequest $request): array
    {
        $data = $request->validated();

        return [
            'employeeIds' => $data['employee_ids'],
            'locationId' => (int) $data['location_id'],
            'startDate' => Carbon::parse($data['start_date']),
            'endDate' => Carbon::parse($data['end_date']),
            'cycleDays' => (int) $data['cycle_days'],
            'direction' => $data['direction'],
            'startShiftId' => isset($data['start_shift_id']) ? (int) $data['start_shift_id'] : null,
            'replace' => (bool) ($data['replace'] ?? false),
            'offDaysPerCycle' => (int) $data['off_days_per_cycle'],
            'offDayMode' => $data['off_day_mode'],
            'fixedOffWeekdays' => $data['off_weekdays'] ?? [],
            'shiftIds' => $data['shift_ids'] ?? null,
        ];
    }

    /**
     * Everything one employee is scheduled for over a date range, behind the
     * "Detail Shift" row action.
     *
     * Two layers, because they answer different questions and can legitimately
     * disagree: the assignments say which shift the rotation put them on, the
     * daily roster rows say what each individual date actually holds — a rest
     * day, a correction made on the roster screen, or a day already clocked in
     * on. Showing only the first would hide every one of those.
     */
    public function employeeShifts(Request $request, Employee $employee): JsonResponse
    {
        [$start, $end] = $this->shiftDetailRange($request);

        $assignments = Assignment::with(['shift:id,shift_code,shift_name', 'location:id,location_name'])
            ->where('employee_id', $employee->id)
            ->overlapping($start->toDateString(), $end->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id,
                'shift_code' => $a->shift->shift_code,
                'shift_name' => $a->shift->shift_name,
                'location_name' => $a->location->location_name,
                'start_date' => $a->start_date->toDateString(),
                'end_date' => $a->end_date?->toDateString(),
                'status' => $a->status,
            ]);

        $rosters = ShiftRoster::with(['shift:id,shift_code,shift_name', 'location:id,location_name', 'attendance:id,roster_id,status,check_in_at'])
            ->where('employee_id', $employee->id)
            ->whereBetween('roster_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('roster_date')
            ->get()
            ->map(fn (ShiftRoster $r) => [
                'id' => $r->id,
                'date' => $r->roster_date->toDateString(),
                'weekday' => $r->roster_date->translatedFormat('D'),
                'shift_code' => $r->shift?->shift_code,
                'shift_name' => $r->shift?->shift_name,
                'location_name' => $r->location->location_name,
                'status' => $r->status,
                'start' => $r->start_datetime?->format('H:i'),
                // The night shift ends the next morning, so its end needs the
                // date alongside it or it reads as ending before it started.
                'end' => $r->end_datetime?->format('d M H:i'),
                'attendance_status' => $r->attendance?->status,
            ]);

        return $this->ok([
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'status' => $employee->status,
            ],
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'assignments' => $assignments,
            'rosters' => $rosters,
            'summary' => [
                'scheduled' => $rosters->where('status', ShiftRoster::SCHEDULED)->count(),
                'off' => $rosters->where('status', ShiftRoster::OFF)->count(),
                'attended' => $rosters->whereNotNull('attendance_status')->count(),
            ],
        ]);
    }

    /**
     * The window the detail covers, defaulting to the current month and capped
     * so one request cannot ask for a year of daily rows.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function shiftDetailRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : Carbon::today()->startOfMonth();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->startOfDay()
            : $start->copy()->endOfMonth();

        if ($end->lessThan($start)) {
            $end = $start->copy()->endOfMonth();
        }

        if ($start->diffInDays($end) > self::MAX_DETAIL_DAYS) {
            $end = $start->copy()->addDays(self::MAX_DETAIL_DAYS);
        }

        return [$start, $end];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Assignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
            'employee_code' => $assignment->employee->employee_code,
            'employee_name' => $assignment->employee->full_name,
            'location_id' => $assignment->location_id,
            'location_name' => $assignment->location->location_name,
            'shift_id' => $assignment->shift_id,
            'shift_code' => $assignment->shift->shift_code,
            'shift_name' => $assignment->shift->shift_name,
            'start_date' => $assignment->start_date->toDateString(),
            'end_date' => $assignment->end_date?->toDateString(),
            'status' => $assignment->status,
        ];
    }
}
