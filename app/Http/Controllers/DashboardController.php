<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\ShiftRoster;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Spec §8 — four counters plus the active payroll summary.
 *
 * "Hari ini" always means attendance_date, never check_in_at, so the night
 * shift that clocks out at 06:00 tomorrow still counts against the day it
 * started (spec §17).
 */
class DashboardController extends Controller
{
    public function index(Request $request, PayrollService $payroll): View|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $data = $user->isHr()
            ? $this->organisationStats($today, $payroll)
            : $this->personalStats($user->employee, $today);

        if ($this->wantsData($request)) {
            return $this->ok($data);
        }

        return view('dashboard.index', [
            'stats' => $data,
            'scope' => $user->isHr() ? 'ORGANISATION' : 'PERSONAL',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function organisationStats(string $today, PayrollService $payroll): array
    {
        $totalEmployee = Employee::active()->count();

        $todayAttendances = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereNotNull('check_in_at');

        $hadir = (clone $todayAttendances)->count();
        $terlambat = (clone $todayAttendances)->where('late_minutes', '>', 0)->count();

        // Scheduled today but no attendance row yet — the people HR needs to
        // chase, as opposed to those simply not rostered.
        $belumAbsen = ShiftRoster::query()
            ->scheduled()
            ->whereDate('roster_date', $today)
            ->whereDoesntHave('attendance')
            ->count();

        $period = PayrollPeriod::active()->orderByDesc('start_date')->first();

        return [
            'scope' => 'ORGANISATION',
            'date' => $today,
            'total_employee' => $totalEmployee,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'belum_absen' => $belumAbsen,
            'dijadwalkan' => $hadir + $belumAbsen,
            'payroll' => $period ? [
                'id' => $period->id,
                'name' => $period->period_name,
                'status' => $period->status,
                'range' => $period->start_date->format('d M Y').' – '.$period->end_date->format('d M Y'),
            ] + $payroll->summary($period) : null,
            'recent' => Attendance::with(['employee:id,employee_code,full_name', 'location:id,location_name'])
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->orderByDesc('check_in_at')
                ->limit(8)
                ->get()
                ->map(fn (Attendance $a) => [
                    'employee' => $a->employee->full_name,
                    'code' => $a->employee->employee_code,
                    'location' => $a->location->location_name,
                    'check_in_at' => $a->check_in_at?->format('H:i'),
                    'check_out_at' => $a->check_out_at?->format('H:i'),
                    'distance' => $a->check_in_distance,
                    'status' => $a->status,
                    'late_minutes' => $a->late_minutes,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personalStats(?Employee $employee, string $today): array
    {
        if (! $employee) {
            return ['scope' => 'PERSONAL', 'linked' => false];
        }

        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $monthEnd = Carbon::today()->endOfMonth()->toDateString();

        $monthly = Attendance::query()
            ->selectRaw('status, COUNT(*) as total')
            ->where('employee_id', $employee->id)
            ->betweenDates($monthStart, $monthEnd)
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) $monthly->get(Attendance::PRESENT, 0);
        $late = (int) $monthly->get(Attendance::LATE, 0);

        $todayRoster = ShiftRoster::with(['shift', 'location'])
            ->where('employee_id', $employee->id)
            ->whereDate('roster_date', $today)
            ->first();

        return [
            'scope' => 'PERSONAL',
            'linked' => true,
            'date' => $today,
            'employee' => [
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'daily_rate' => (float) $employee->daily_rate,
            ],
            'present_this_month' => $present,
            'late_this_month' => $late,
            'working_days_this_month' => $present + $late,
            'incomplete_this_month' => (int) $monthly->get(Attendance::INCOMPLETE, 0),
            'estimated_earning' => ($present + $late) * (float) $employee->daily_rate,
            'today_roster' => $todayRoster ? [
                'shift' => $todayRoster->shift?->shift_name ?? 'OFF',
                'location' => $todayRoster->location->location_name,
                'start' => $todayRoster->start_datetime?->format('d M Y H:i'),
                'end' => $todayRoster->end_datetime?->format('d M Y H:i'),
                'status' => $todayRoster->status,
            ] : null,
        ];
    }
}
