<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Spec §60 phase 6 — recap tables plus a plain CSV export.
 *
 * CSV rather than XLSX keeps the MVP free of a spreadsheet dependency; Excel
 * opens it directly, which is what the recap is actually used for.
 */
class ReportController extends Controller
{
    /**
     * Attendance recap: one row per employee with their day counts for the
     * selected range.
     */
    public function attendance(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('reports.attendance');
        }

        [$start, $end] = $this->resolveRange($request);

        $employees = $this->attendanceQuery($request)->paginate($this->perPage($request, 25));

        // Counted once for the page on screen rather than for every employee in
        // the database — the recap is a per-employee roll-up, so the aggregate
        // only has to cover the rows being drawn.
        $counts = $this->attendanceCounts($request, $start, $end, $employees->getCollection()->modelKeys());

        return $this->withExtra(
            $this->paginated($employees, fn (Employee $e) => $this->attendanceRow($e, $counts)),
            [
                'range' => ['start' => $start, 'end' => $end],
                // Aggregated over every employee the filters match, not just the
                // page on screen — a footer that changed as you paged would be
                // worse than no footer at all.
                'totals' => $this->attendanceTotals($request, $start, $end),
            ],
        );
    }

    public function exportAttendance(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);

        // The export is the whole recap, never just the page on screen.
        $employees = $this->attendanceQuery($request)->get();
        $counts = $this->attendanceCounts($request, $start, $end, $employees->modelKeys());

        $rows = $employees->map(fn (Employee $e) => $this->attendanceRow($e, $counts))->values();

        return $this->csv(
            sprintf('laporan-absensi_%s_sd_%s.csv', $start, $end),
            ['Kode', 'Nama Karyawan', 'Hadir', 'Terlambat', 'Belum Lengkap', 'Alpha', 'Hari Kerja', 'Total Menit Terlambat'],
            $rows->map(fn (array $r) => [
                $r['employee_code'],
                $r['employee_name'],
                $r['present'],
                $r['late'],
                $r['incomplete'],
                $r['absent'],
                $r['working_days'],
                $r['late_minutes'],
            ]),
        );
    }

    /**
     * Payroll recap for one period (spec §43).
     */
    public function payroll(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('reports.payroll');
        }

        $period = $this->resolvePeriod($request);

        if (! $period) {
            return $request->has('draw')
                ? response()->json([
                    'draw' => (int) $request->input('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'period' => null,
                    'totals' => null,
                ])
                : $this->ok(['period' => null, 'items' => [], 'totals' => null]);
        }

        $payrolls = $this->payrollQuery($period, $request)->paginate($this->perPage($request, 25));

        return $this->withExtra(
            $this->paginated($payrolls, fn (Payroll $p) => $this->payrollRow($p)),
            [
                'period' => [
                    'id' => $period->id,
                    'name' => $period->period_name,
                    'status' => $period->status,
                    'range' => $period->start_date->toDateString().' s/d '.$period->end_date->toDateString(),
                ],
                // Totals are aggregated over the whole filtered period, never
                // over the page being drawn — a footer that changed as you
                // paged would be worse than no footer at all.
                'totals' => $this->payrollTotals($period, $request),
            ],
        );
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $period = $this->resolvePeriod($request);

        abort_unless($period, 404, 'Periode payroll tidak ditemukan.');

        $rows = $this->payrollQuery($period, $request)->get()
            ->map(fn (Payroll $p) => $this->payrollRow($p))
            ->values();

        return $this->csv(
            sprintf('laporan-payroll_%s.csv', $period->period_code),
            ['Kode', 'Nama Karyawan', 'Hadir', 'Terlambat', 'Hari Kerja', 'Upah Harian', 'Gross', 'Potongan', 'Net'],
            $rows->map(fn (array $r) => [
                $r['employee_code'],
                $r['employee_name'],
                $r['present_days'],
                $r['late_days'],
                $r['working_days'],
                $r['daily_rate'],
                $r['gross_salary'],
                $r['total_deduction'],
                $r['net_salary'],
            ]),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Row builders — shared by the on-screen table and the CSV
    |--------------------------------------------------------------------------
    */

    /** The employees a recap covers, filtered and ordered. */
    private function attendanceQuery(Request $request)
    {
        return Employee::query()
            ->when($request->filled('employee_id'), fn ($q) => $q->whereKey($request->integer('employee_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->active())
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'employee_code' => 'employee_code',
                'employee_name' => 'full_name',
                'daily_rate' => 'daily_rate',
            ], 'employee_code'));
    }

    /**
     * Day counts per employee for the range, keyed by employee id.
     *
     * @param  array<int, int>  $employeeIds
     */
    private function attendanceCounts(Request $request, string $start, string $end, array $employeeIds)
    {
        return Attendance::query()
            ->selectRaw('employee_id, status, COUNT(*) as total, COALESCE(SUM(late_minutes), 0) as late_minutes')
            ->betweenDates($start, $end)
            ->whereIn('employee_id', $employeeIds)
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->groupBy('employee_id', 'status')
            ->get()
            ->groupBy('employee_id');
    }

    /**
     * Day counts rolled up across every employee the filters match.
     *
     * @return array<string, int>
     */
    private function attendanceTotals(Request $request, string $start, string $end): array
    {
        $rows = Attendance::query()
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(late_minutes), 0) as late_minutes')
            ->betweenDates($start, $end)
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            // A subquery rather than a list of ids, so the roll-up costs one
            // round-trip however many employees the filter matches.
            ->whereIn('employee_id', $this->attendanceQuery($request)->reorder()->select('id'))
            ->groupBy('status')
            ->get();

        $get = fn (string $status) => (int) ($rows->firstWhere('status', $status)->total ?? 0);

        $present = $get(Attendance::PRESENT);
        $late = $get(Attendance::LATE);

        return [
            'present' => $present,
            'late' => $late,
            'incomplete' => $get(Attendance::INCOMPLETE),
            'absent' => $get(Attendance::ABSENT),
            'working_days' => $present + $late,
            'late_minutes' => (int) $rows->sum('late_minutes'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceRow(Employee $employee, $counts): array
    {
        $rows = $counts->get($employee->id, collect());

        $get = fn (string $status) => (int) ($rows->firstWhere('status', $status)->total ?? 0);

        $present = $get(Attendance::PRESENT);
        $late = $get(Attendance::LATE);

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'present' => $present,
            'late' => $late,
            'incomplete' => $get(Attendance::INCOMPLETE),
            'absent' => $get(Attendance::ABSENT),
            // Spec §39 — PRESENT + LATE, nothing else.
            'working_days' => $present + $late,
            'late_minutes' => (int) $rows->sum('late_minutes'),
            'daily_rate' => (float) $employee->daily_rate,
        ];
    }

    /** The payroll lines a recap covers, filtered and ordered. */
    private function payrollQuery(PayrollPeriod $period, Request $request)
    {
        return $period->payrolls()
            ->with('employee:id,employee_code,full_name')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->whereHas('employee', fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->select('payrolls.*')
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'employee_code' => 'employees.employee_code',
                'employee_name' => 'employees.full_name',
                'working_days' => 'payrolls.working_days',
                'gross_salary' => 'payrolls.gross_salary',
                'total_deduction' => 'payrolls.total_deduction',
                'net_salary' => 'payrolls.net_salary',
            ], 'employee_code'));
    }

    /**
     * @return array<string, float|int>
     */
    private function payrollTotals(PayrollPeriod $period, Request $request): array
    {
        // select() rather than selectRaw(): the shared query already selects
        // payrolls.*, and appending an aggregate to concrete columns with no
        // GROUP BY is rejected outright by MySQL.
        $totals = $this->payrollQuery($period, $request)
            ->reorder()
            ->select(DB::raw(
                'COALESCE(SUM(payrolls.working_days), 0) as working_days,'.
                'COALESCE(SUM(payrolls.gross_salary), 0) as gross,'.
                'COALESCE(SUM(payrolls.total_deduction), 0) as deduction,'.
                'COALESCE(SUM(payrolls.net_salary), 0) as net'
            ))
            ->first();

        return [
            'working_days' => (int) $totals->working_days,
            'gross' => (float) $totals->gross,
            'deduction' => (float) $totals->deduction,
            'net' => (float) $totals->net,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollRow(Payroll $p): array
    {
        return [
            'employee_code' => $p->employee->employee_code,
            'employee_name' => $p->employee->full_name,
            'present_days' => $p->present_days,
            'late_days' => $p->late_days,
            'working_days' => $p->working_days,
            'daily_rate' => (float) $p->daily_rate,
            'gross_salary' => (float) $p->gross_salary,
            'total_deduction' => (float) $p->total_deduction,
            'net_salary' => (float) $p->net_salary,
            'status' => $p->status,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))
            : Carbon::today()->startOfMonth();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))
            : Carbon::today()->endOfMonth();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    private function resolvePeriod(Request $request): ?PayrollPeriod
    {
        return $request->filled('period_id')
            ? PayrollPeriod::find($request->integer('period_id'))
            : PayrollPeriod::orderByDesc('start_date')->first();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  \Illuminate\Support\Collection<int, array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $headers, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'wb');

            // BOM so Excel on Windows reads the file as UTF-8 rather than
            // mangling accented names.
            fwrite($handle, "\xEF\xBB\xBF");

            // Semicolon delimiter matches the Indonesian Excel locale, where a
            // comma is the decimal separator.
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
