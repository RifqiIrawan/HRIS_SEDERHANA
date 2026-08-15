<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceException;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Employee;
use App\Models\ShiftRoster;
use App\Services\AttendanceService;
use App\Services\GeocodingService;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Spec §19-§33 & §47 (Attendance). */
class AttendanceController extends Controller
{
    /**
     * Columns the history and monitoring tables may be ordered by. Employee,
     * location and shift are deliberately absent: they live on related tables
     * and sorting them would need a join the listing does not otherwise pay for.
     *
     * @var array<string, string|string[]>
     */
    private const SORTABLE = [
        'attendance_date' => ['attendance_date', 'check_in_at'],
        'check_in_at' => 'check_in_at',
        'check_out_at' => 'check_out_at',
        'check_in_distance' => 'check_in_distance',
        'check_in_accuracy' => 'check_in_accuracy',
        'late_minutes' => 'late_minutes',
        'status' => 'status',
    ];

    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * The employee-facing check-in / check-out screen. Rendered as HTML for a
     * normal visit; the same URL returns the live context for the AJAX poll
     * that keeps the screen in step with the server (spec §47 `GET /attendance`).
     */
    public function index(Request $request): View|JsonResponse
    {
        $employee = $this->currentEmployee($request);

        $context = $this->attendance->context($employee);

        if (! $this->wantsData($request)) {
            return view('attendance.check-in', [
                'employee' => $employee,
                'context' => $context,
            ]);
        }

        return $this->ok($this->contextPayload($context));
    }

    /**
     * The address behind a live GPS reading, for the check-in screen's own
     * display. It is a convenience lookup only: what actually gets stored is
     * resolved again server-side from the coordinates the check-in submits, so
     * nothing here can influence the recorded address.
     */
    public function geocode(Request $request, GeocodingService $geocoder): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return $this->ok([
            'address' => $geocoder->reverseGeocode(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
            ),
        ]);
    }

    public function checkIn(AttendanceRequest $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);

        $attendance = $this->attendance->checkIn(
            employee: $employee,
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            accuracy: (float) $request->input('accuracy'),
            photo: $request->file('photo'),
        );

        return $this->ok(
            $this->transform($attendance),
            sprintf(
                'Check-in berhasil pada %s. Jarak %.1f m dari titik lokasi.',
                $attendance->check_in_at->format('H:i'),
                $attendance->check_in_distance,
            ),
            201,
        );
    }

    public function checkOut(AttendanceRequest $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);

        $attendance = $this->attendance->checkOut(
            employee: $employee,
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            accuracy: (float) $request->input('accuracy'),
            photo: $request->file('photo'),
        );

        return $this->ok(
            $this->transform($attendance),
            sprintf(
                'Check-out berhasil pada %s. Status kehadiran: %s.',
                $attendance->check_out_at->format('H:i'),
                $attendance->status,
            ),
        );
    }

    /**
     * Spec §32. An EMPLOYEE only ever sees their own rows; HR may filter by
     * employee.
     */
    public function history(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (! $this->wantsData($request)) {
            return view('attendance.history', ['isHr' => $user->isHr()]);
        }

        $query = $this->baseQuery($request);

        if (! $user->isHr()) {
            $query->where('employee_id', $this->currentEmployee($request)->id);
        }

        $attendances = $query
            ->tap(fn ($q) => $this->applySort($q, $request, self::SORTABLE, 'attendance_date', 'desc'))
            ->paginate($this->perPage($request));

        return $this->paginated($attendances, fn (Attendance $a) => $this->transform($a));
    }

    /** Spec §33 — HR monitoring with the same filters plus a daily summary. */
    public function monitoring(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('attendance.monitoring');
        }

        $attendances = $this->baseQuery($request)
            ->tap(fn ($q) => $this->applySort($q, $request, self::SORTABLE, 'attendance_date', 'desc'))
            ->paginate($this->perPage($request, 25));

        return $this->withExtra(
            $this->paginated($attendances, fn (Attendance $a) => $this->transform($a)),
            ['summary' => $this->monitoringSummary($request)],
        );
    }

    public function show(Request $request, Attendance $attendance): JsonResponse
    {
        $user = $request->user();

        if (! $user->isHr() && $attendance->employee_id !== $user->employee_id) {
            return $this->fail('Anda tidak memiliki akses ke data absensi ini.', 403);
        }

        return $this->ok($this->transform(
            $attendance->load(['employee', 'location', 'roster.shift', 'photos']),
            detailed: true,
        ));
    }

    /**
     * Shared filter set for history and monitoring (spec §33).
     */
    private function baseQuery(Request $request)
    {
        return Attendance::query()
            ->with([
                'employee:id,employee_code,full_name',
                'location:id,location_name',
                'roster:id,shift_id',
                'roster.shift:id,shift_code,shift_name',
                'photos',
            ])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('shift_id'), fn ($q) => $q->whereHas(
                'roster',
                fn ($r) => $r->where('shift_id', $request->integer('shift_id')),
            ))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->whereHas('employee', fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->date('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->date('end_date')))
            // With no explicit range, monitoring means "today".
            ->when(
                ! $request->filled('start_date') && ! $request->filled('end_date') && $request->boolean('today', false),
                fn ($q) => $q->whereDate('attendance_date', Carbon::today()),
            );
    }

    /**
     * @return array<string, int>
     */
    private function monitoringSummary(Request $request): array
    {
        $counts = (clone $this->baseQuery($request))
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $date = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))
            : Carbon::today();

        // Scheduled but with no attendance row — the "belum absen" figure the
        // dashboard and monitoring screen both quote.
        $notCheckedIn = ShiftRoster::query()
            ->scheduled()
            ->whereDate('roster_date', $date)
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->whereDoesntHave('attendance')
            ->count();

        return [
            'present' => (int) $counts->get(Attendance::PRESENT, 0),
            'late' => (int) $counts->get(Attendance::LATE, 0),
            'incomplete' => (int) $counts->get(Attendance::INCOMPLETE, 0),
            'absent' => (int) $counts->get(Attendance::ABSENT, 0),
            'not_checked_in' => $notCheckedIn,
        ];
    }

    /**
     * @param  array{roster: ?ShiftRoster, attendance: ?Attendance, state: string}  $context
     * @return array<string, mixed>
     */
    private function contextPayload(array $context): array
    {
        $roster = $context['roster'];
        $location = $roster?->location;

        return [
            'state' => $context['state'],
            'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            // Lets the screen unlock its own accuracy gate — and say why — when
            // the server is not enforcing one either.
            'enforce_geofence' => GeofenceService::isEnforced(),
            'roster' => $roster ? [
                'id' => $roster->id,
                'date' => $roster->roster_date->toDateString(),
                'shift_code' => $roster->shift?->shift_code,
                'shift_name' => $roster->shift?->shift_name,
                'start' => $roster->start_datetime?->format('Y-m-d H:i'),
                'end' => $roster->end_datetime?->format('Y-m-d H:i'),
                'cross_day' => (bool) $roster->shift?->cross_day,
                'late_threshold' => $roster->start_datetime
                    ?->copy()
                    ->addMinutes($roster->shift?->late_tolerance_minutes ?? 0)
                    ->format('H:i'),
            ] : null,
            'location' => $location ? [
                'id' => $location->id,
                'name' => $location->location_name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'radius_meter' => $location->effectiveRadiusMeter(),
                'gps_accuracy_limit' => $location->effectiveAccuracyLimit(),
            ] : null,
            'attendance' => $context['attendance'] ? $this->transform($context['attendance']) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Attendance $attendance, bool $detailed = false): array
    {
        $data = [
            'id' => $attendance->id,
            'attendance_date' => $attendance->attendance_date->toDateString(),
            'employee_code' => $attendance->employee?->employee_code,
            'employee_name' => $attendance->employee?->full_name,
            'location_name' => $attendance->location?->location_name,
            'shift_code' => $attendance->roster?->shift?->shift_code,
            'shift_name' => $attendance->roster?->shift?->shift_name,
            'check_in_at' => $attendance->check_in_at?->format('Y-m-d H:i:s'),
            'check_in_distance' => $attendance->check_in_distance,
            'check_in_accuracy' => $attendance->check_in_accuracy,
            'check_in_address' => $attendance->check_in_address,
            'check_out_at' => $attendance->check_out_at?->format('Y-m-d H:i:s'),
            'check_out_distance' => $attendance->check_out_distance,
            'check_out_accuracy' => $attendance->check_out_accuracy,
            'check_out_address' => $attendance->check_out_address,
            'late_minutes' => $attendance->late_minutes,
            'status' => $attendance->status,
            'photos' => $attendance->relationLoaded('photos')
                ? $attendance->photos->mapWithKeys(fn (AttendancePhoto $p) => [
                    $p->photo_type => route('attendance.photo', $p),
                ])
                : null,
        ];

        if (! $detailed) {
            return $data;
        }

        return $data + [
            'check_in_latitude' => $attendance->check_in_latitude,
            'check_in_longitude' => $attendance->check_in_longitude,
            'check_out_latitude' => $attendance->check_out_latitude,
            'check_out_longitude' => $attendance->check_out_longitude,
            'location' => $attendance->location ? [
                'latitude' => $attendance->location->latitude,
                'longitude' => $attendance->location->longitude,
                'radius_meter' => $attendance->location->effectiveRadiusMeter(),
            ] : null,
        ];
    }

    /**
     * AC-002 — the employee always comes from the authenticated session, never
     * from a request field.
     */
    private function currentEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw AttendanceException::notAnEmployee();
        }

        return $employee;
    }
}
