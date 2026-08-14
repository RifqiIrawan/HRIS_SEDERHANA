<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;

/**
 * One call that fills every <select> on a page. Pages with four dropdowns
 * would otherwise fire four requests before they can render.
 */
class LookupController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return $this->ok([
            'employees' => Employee::active()
                ->orderBy('employee_code')
                ->get(['id', 'employee_code', 'full_name', 'daily_rate'])
                ->map(fn (Employee $e) => [
                    'id' => $e->id,
                    'code' => $e->employee_code,
                    'label' => $e->employee_code.' — '.$e->full_name,
                    'daily_rate' => (float) $e->daily_rate,
                ]),

            'locations' => Location::active()
                ->orderBy('location_name')
                ->get(['id', 'location_code', 'location_name', 'latitude', 'longitude', 'radius_meter', 'gps_accuracy_limit'])
                ->map(fn (Location $l) => [
                    'id' => $l->id,
                    'code' => $l->location_code,
                    'label' => $l->location_name,
                    'latitude' => $l->latitude,
                    'longitude' => $l->longitude,
                    'radius_meter' => $l->radius_meter,
                    'gps_accuracy_limit' => $l->gps_accuracy_limit,
                ]),

            'shifts' => Shift::active()
                ->orderBy('start_time')
                ->get(['id', 'shift_code', 'shift_name', 'start_time', 'end_time', 'cross_day', 'late_tolerance_minutes'])
                ->map(fn (Shift $s) => [
                    'id' => $s->id,
                    'code' => $s->shift_code,
                    'label' => sprintf(
                        '%s (%s–%s)',
                        $s->shift_name,
                        substr($s->start_time, 0, 5),
                        substr($s->end_time, 0, 5),
                    ),
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time' => substr($s->end_time, 0, 5),
                    'cross_day' => $s->cross_day,
                    'late_tolerance_minutes' => $s->late_tolerance_minutes,
                ]),

            'roles' => Role::where('status', 'ACTIVE')
                ->orderBy('role_code')
                ->get(['id', 'role_code', 'role_name'])
                ->map(fn (Role $r) => [
                    'id' => $r->id,
                    'code' => $r->role_code,
                    'label' => $r->role_name,
                ]),

            'periods' => PayrollPeriod::orderByDesc('start_date')
                ->get(['id', 'period_code', 'period_name', 'start_date', 'end_date', 'status'])
                ->map(fn (PayrollPeriod $p) => [
                    'id' => $p->id,
                    'code' => $p->period_code,
                    'label' => $p->period_name,
                    'start_date' => $p->start_date->toDateString(),
                    'end_date' => $p->end_date->toDateString(),
                    'status' => $p->status,
                ]),
        ]);
    }
}
