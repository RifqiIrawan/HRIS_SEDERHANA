<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\ShiftRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * "Jadwal shift saya" — the roster rows belonging to the token's employee.
 *
 * The web RosterController is a planning screen for HR: it lists everybody and
 * lets rows be edited. Nothing of that is reusable here, so this is a separate,
 * deliberately read-only surface over the same table.
 */
class ScheduleController extends Controller
{
    use ResolvesEmployee;

    /** A phone showing a fortnight is the common case; a month is the maximum. */
    private const DEFAULT_DAYS = 14;

    private const MAX_DAYS = 62;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        $employee = $this->currentEmployee($request);

        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : Carbon::today();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->startOfDay()
            : $start->copy()->addDays(self::DEFAULT_DAYS - 1);

        // A phone asking for a decade would page the whole roster table into
        // memory; clamp rather than refuse, so the screen still renders.
        if ($start->diffInDays($end) > self::MAX_DAYS) {
            $end = $start->copy()->addDays(self::MAX_DAYS);
        }

        $rosters = ShiftRoster::query()
            ->with(['shift:id,shift_code,shift_name,cross_day,late_tolerance_minutes', 'location:id,location_name,address'])
            // The attendance row is what turns "dijadwalkan" into "sudah absen"
            // on the list, without a second request per day.
            ->with(['attendance:id,roster_id,status,check_in_at,check_out_at'])
            ->where('employee_id', $employee->id)
            ->whereBetween('roster_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('roster_date')
            ->orderBy('start_datetime')
            ->get();

        return $this->ok([
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'items' => $rosters->map(fn (ShiftRoster $roster) => [
                'id' => $roster->id,
                'date' => $roster->roster_date->toDateString(),
                'day_label' => $roster->roster_date->translatedFormat('D, d M Y'),
                'status' => $roster->status,
                'is_working_day' => $roster->isWorkingDay(),
                'shift_code' => $roster->shift?->shift_code,
                'shift_name' => $roster->shift?->shift_name,
                'cross_day' => (bool) $roster->shift?->cross_day,
                'start' => $roster->start_datetime?->format('Y-m-d H:i'),
                'end' => $roster->end_datetime?->format('Y-m-d H:i'),
                'location_name' => $roster->location?->location_name,
                'location_address' => $roster->location?->address,
                'attendance' => $roster->attendance ? [
                    'id' => $roster->attendance->id,
                    'status' => $roster->attendance->status,
                    'check_in_at' => $roster->attendance->check_in_at?->format('Y-m-d H:i:s'),
                    'check_out_at' => $roster->attendance->check_out_at?->format('Y-m-d H:i:s'),
                ] : null,
            ])->values(),
        ]);
    }
}
