<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AttendanceController as WebAttendanceController;
use App\Models\Attendance;
use App\Models\AttendancePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The attendance surface the Flutter client talks to.
 *
 * It extends the web controller rather than restating it, because check-in is
 * the one flow where a second implementation would be genuinely dangerous: the
 * geofence verdict, the shift window, the late calculation and the photo
 * handling all have to stay byte-identical to what the browser does, or the
 * same employee could be judged differently depending on the device in their
 * hand. Only two things actually differ, and both are overridden below.
 */
class AttendanceController extends WebAttendanceController
{
    /**
     * The web route streams photos behind the session guard, which a bearer
     * token cannot satisfy. Point the payload at the API twin instead — same
     * controller, same ownership check, different way of proving who you are.
     */
    protected function photoUrl(AttendancePhoto $photo): string
    {
        return route('api.attendance.photo', $photo);
    }

    /**
     * "Riwayat saya" — always the caller's own rows.
     *
     * The web listing widens for HR, which is right for the monitoring screen
     * but wrong for a personal app: an HR user opening the phone wants their own
     * attendance, not the whole company's. Pinning employee_id before delegating
     * also closes the filter, so a crafted ?employee_id= cannot read anyone else.
     */
    public function history(Request $request): JsonResponse
    {
        $request->merge(['employee_id' => $this->currentEmployee($request)->id]);

        /** @var JsonResponse $response */
        $response = parent::history($request);

        return $response;
    }

    /**
     * Month-at-a-glance counters for the history screen's header.
     *
     * Cheap to compute and awkward on a phone otherwise — paging the whole month
     * client-side just to count four statuses would cost far more requests.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate(
            ['month' => ['nullable', 'date_format:Y-m']],
            ['month.date_format' => 'Format bulan harus YYYY-MM.'],
        );

        $employee = $this->currentEmployee($request);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', (string) $request->string('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [
                $month->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);

        $counts = (clone $rows)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->ok([
            'month' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'present' => (int) $counts->get(Attendance::PRESENT, 0),
            'late' => (int) $counts->get(Attendance::LATE, 0),
            'incomplete' => (int) $counts->get(Attendance::INCOMPLETE, 0),
            'absent' => (int) $counts->get(Attendance::ABSENT, 0),
            'total_late_minutes' => (int) (clone $rows)->sum('late_minutes'),
        ]);
    }
}
