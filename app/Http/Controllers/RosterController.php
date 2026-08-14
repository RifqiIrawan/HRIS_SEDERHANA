<?php

namespace App\Http\Controllers;

use App\Http\Requests\RosterGenerateRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Services\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Spec §15-§17 & §47 (Roster). */
class RosterController extends Controller
{
    /** A calendar grid wider than this stops being readable on any screen. */
    private const MAX_RANGE_DAYS = 62;

    public function __construct(private readonly RosterService $rosters) {}

    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('rosters.index');
        }

        [$start, $end] = $this->resolveRange($request);

        $employees = Employee::active()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'employee_code' => 'employee_code',
                'employee_name' => 'full_name',
            ], 'employee_code'))
            ->paginate($this->perPage($request, 20));

        $rosters = ShiftRoster::query()
            ->with(['shift:id,shift_code,shift_name', 'location:id,location_name'])
            ->whereIn('employee_id', collect($employees->items())->pluck('id'))
            ->whereBetween('roster_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->get()
            ->groupBy('employee_id');

        $dates = collect();
        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $dates->push([
                'date' => $date->toDateString(),
                'day' => $date->format('d'),
                'weekday' => $date->translatedFormat('D'),
                'is_weekend' => $date->isWeekend(),
            ]);
        }

        $response = $this->paginated($employees, function (Employee $employee) use ($rosters) {
            $cells = $rosters->get($employee->id, collect())
                ->keyBy(fn (ShiftRoster $r) => $r->roster_date->toDateString())
                ->map(fn (ShiftRoster $r) => [
                    'id' => $r->id,
                    'shift_id' => $r->shift_id,
                    'shift_code' => $r->shift?->shift_code,
                    'location_id' => $r->location_id,
                    'location_name' => $r->location->location_name,
                    'status' => $r->status,
                    'start' => $r->start_datetime?->format('d M H:i'),
                    'end' => $r->end_datetime?->format('d M H:i'),
                ]);

            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->full_name,
                'cells' => $cells,
            ];
        });

        // The date header travels with the rows: the columns the table draws
        // are derived from it, so the two must always describe the same range.
        return $this->withExtra($response, [
            'dates' => $dates,
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ]);
    }

    public function generate(RosterGenerateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->rosters->generate(
            employeeIds: $data['employee_ids'],
            locationId: $data['location_id'],
            startDate: Carbon::parse($data['start_date']),
            endDate: Carbon::parse($data['end_date']),
            pattern: $data['pattern'],
            overwrite: $data['overwrite'] ?? false,
        );

        return $this->ok($result, sprintf(
            '%d jadwal dibuat, %d diperbarui, %d dilewati.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));
    }

    /** Dry run so HR can see what a pattern produces before committing. */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pattern' => ['required', 'string', 'max:200'],
        ]);

        $pattern = array_map('strtoupper', array_map('trim', explode(',', $data['pattern'])));

        return $this->ok($this->rosters->preview(
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            $pattern,
        ));
    }

    public function show(ShiftRoster $roster): JsonResponse
    {
        $roster->load(['shift', 'location', 'employee', 'attendance']);

        return $this->ok([
            'id' => $roster->id,
            'employee_id' => $roster->employee_id,
            'employee_label' => $roster->employee->employee_code.' — '.$roster->employee->full_name,
            'location_id' => $roster->location_id,
            'shift_id' => $roster->shift_id,
            'roster_date' => $roster->roster_date->toDateString(),
            'start_datetime' => $roster->start_datetime?->format('Y-m-d H:i'),
            'end_datetime' => $roster->end_datetime?->format('Y-m-d H:i'),
            'status' => $roster->status,
            'has_attendance' => $roster->attendance !== null,
        ]);
    }

    public function update(Request $request, ShiftRoster $roster): JsonResponse
    {
        if ($roster->attendance) {
            return $this->fail(
                'Jadwal ini sudah memiliki absensi dan tidak dapat diubah.',
                422,
                'ROSTER_HAS_ATTENDANCE',
            );
        }

        $data = $request->validate([
            'location_id' => ['required', Rule::exists('locations', 'id')->where('status', 'ACTIVE')],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('status', 'ACTIVE')],
            'status' => ['required', Rule::in([ShiftRoster::SCHEDULED, ShiftRoster::OFF, ShiftRoster::CANCELLED])],
        ]);

        $shift = $data['shift_id'] ? Shift::find($data['shift_id']) : null;

        // A SCHEDULED day without a shift has no start or end, which would make
        // it invisible to the check-in lookup.
        if ($data['status'] === ShiftRoster::SCHEDULED && ! $shift) {
            return $this->fail('Pilih shift untuk jadwal berstatus SCHEDULED.', 422, 'SHIFT_REQUIRED');
        }

        $roster->update([
            'location_id' => $data['location_id'],
            'shift_id' => $shift?->id,
            'status' => $data['status'],
            'start_datetime' => $shift?->startDatetimeFor($roster->roster_date),
            'end_datetime' => $shift?->endDatetimeFor($roster->roster_date),
        ]);

        AuditLog::record('roster.updated', $roster, sprintf(
            'Roster %s tanggal %s diubah',
            $roster->employee->employee_code,
            $roster->roster_date->format('d-m-Y'),
        ));

        return $this->ok(message: 'Jadwal berhasil diperbarui.');
    }

    public function destroy(ShiftRoster $roster): JsonResponse
    {
        if ($roster->attendance) {
            return $this->fail(
                'Jadwal ini sudah memiliki absensi dan tidak dapat dihapus.',
                422,
                'ROSTER_HAS_ATTENDANCE',
            );
        }

        $roster->delete();

        AuditLog::record('roster.deleted', null, 'Roster #'.$roster->id.' dihapus');

        return $this->ok(message: 'Jadwal berhasil dihapus.');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : Carbon::today()->startOfMonth();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->startOfDay()
            : $start->copy()->endOfMonth();

        if ($end->lessThan($start)) {
            $end = $start->copy();
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            $end = $start->copy()->addDays(self::MAX_RANGE_DAYS);
        }

        return [$start, $end];
    }
}
