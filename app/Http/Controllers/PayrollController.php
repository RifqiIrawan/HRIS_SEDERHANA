<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollPeriodRequest;
use App\Models\AuditLog;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §36-§44 & §47 (Payroll). */
class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    /*
    |--------------------------------------------------------------------------
    | Periods
    |--------------------------------------------------------------------------
    */

    public function periods(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('payroll.periods');
        }

        $periods = PayrollPeriod::query()
            ->withCount('payrolls')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'period_code' => 'period_code',
                'period_name' => 'period_name',
                'start_date' => 'start_date',
                'end_date' => 'end_date',
                'status' => 'status',
            ], 'start_date', 'desc'))
            ->paginate($this->perPage($request));

        return $this->paginated($periods, fn (PayrollPeriod $p) => $this->transformPeriod($p));
    }

    public function storePeriod(PayrollPeriodRequest $request): JsonResponse
    {
        $period = PayrollPeriod::create($request->validated() + ['status' => PayrollPeriod::OPEN]);

        AuditLog::record('payroll.period_created', $period, 'Periode '.$period->period_name.' dibuat');

        return $this->ok($this->transformPeriod($period), 'Periode payroll berhasil dibuat.', 201);
    }

    public function showPeriod(PayrollPeriod $period): JsonResponse
    {
        return $this->ok($this->transformPeriod($period));
    }

    public function updatePeriod(PayrollPeriodRequest $request, PayrollPeriod $period): JsonResponse
    {
        if ($period->isClosed()) {
            return $this->fail('Periode sudah ditutup dan tidak dapat diubah.', 422, 'PERIOD_CLOSED');
        }

        $period->update($request->validated());

        AuditLog::record('payroll.period_updated', $period, 'Periode '.$period->period_name.' diperbarui');

        return $this->ok($this->transformPeriod($period), 'Periode payroll berhasil diperbarui.');
    }

    public function destroyPeriod(PayrollPeriod $period): JsonResponse
    {
        if ($period->isClosed()) {
            return $this->fail('Periode sudah ditutup dan tidak dapat dihapus.', 422, 'PERIOD_CLOSED');
        }

        $name = $period->period_name;
        // Payroll lines and their details cascade with the period.
        $period->delete();

        AuditLog::record('payroll.period_deleted', null, 'Periode '.$name.' dihapus');

        return $this->ok(message: 'Periode payroll berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Payroll lines
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('payroll.index');
        }

        $period = $request->filled('period_id')
            ? PayrollPeriod::find($request->integer('period_id'))
            : PayrollPeriod::active()->orderByDesc('start_date')->first();

        if (! $period) {
            // Still a valid DataTables draw — an empty table, not an error.
            return $request->has('draw')
                ? response()->json([
                    'draw' => (int) $request->input('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'period' => null,
                    'summary' => null,
                ])
                : $this->ok(['period' => null, 'items' => [], 'summary' => null]);
        }

        return $this->periodPayload($period, $request);
    }

    public function show(Request $request, PayrollPeriod $period): JsonResponse
    {
        return $this->periodPayload($period, $request);
    }

    /** Spec §38. */
    public function generate(PayrollPeriod $period): JsonResponse
    {
        $summary = $this->payroll->generate($period);

        return $this->ok(
            $summary,
            sprintf('Payroll berhasil digenerate untuk %d karyawan.', $summary['employees']),
        );
    }

    /** Spec §44. */
    public function close(PayrollPeriod $period): JsonResponse
    {
        $this->payroll->close($period);

        return $this->ok($this->transformPeriod($period->refresh()), 'Periode payroll ditutup.');
    }

    /** Spec §44 — ADMIN only, enforced by the route middleware. */
    public function reopen(PayrollPeriod $period): JsonResponse
    {
        $this->payroll->reopen($period);

        return $this->ok($this->transformPeriod($period->refresh()), 'Periode payroll dibuka kembali.');
    }

    /*
    |--------------------------------------------------------------------------
    | Deductions — spec §41, §42
    |--------------------------------------------------------------------------
    */

    public function storeDeduction(Request $request, Payroll $payroll): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999999'],
        ], [], [
            'description' => 'Keterangan',
            'amount' => 'Jumlah',
        ]);

        $this->payroll->addDeduction($payroll, $data['description'], (float) $data['amount']);

        return $this->ok($this->transformPayroll($payroll->refresh()->load('details')), 'Potongan ditambahkan.', 201);
    }

    public function destroyDeduction(PayrollDetail $detail): JsonResponse
    {
        $payroll = $detail->payroll;

        $this->payroll->removeDetail($detail);

        return $this->ok($this->transformPayroll($payroll->refresh()->load('details')), 'Potongan dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Payload builders
    |--------------------------------------------------------------------------
    */

    /**
     * The payroll lines for one period, paginated.
     *
     * The period header and its totals ride along with every page: they are
     * what the summary strip above the table shows, and regenerating or adding
     * a deduction has to move both in step.
     */
    private function periodPayload(PayrollPeriod $period, Request $request): JsonResponse
    {
        $payrolls = $period->payrolls()
            ->with(['employee:id,employee_code,full_name', 'details'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->whereHas('employee', fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            // Joined so the default order can follow the employee code rather
            // than the insertion order of the generate run.
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->select('payrolls.*')
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'employee_code' => 'employees.employee_code',
                'employee_name' => 'employees.full_name',
                'working_days' => 'payrolls.working_days',
                'daily_rate' => 'payrolls.daily_rate',
                'gross_salary' => 'payrolls.gross_salary',
                'total_deduction' => 'payrolls.total_deduction',
                'net_salary' => 'payrolls.net_salary',
            ], 'employee_code'))
            ->paginate($this->perPage($request, 25));

        return $this->withExtra(
            $this->paginated($payrolls, fn (Payroll $p) => $this->transformPayroll($p)),
            [
                'period' => $this->transformPeriod($period),
                'summary' => $this->payroll->summary($period),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPeriod(PayrollPeriod $period): array
    {
        return [
            'id' => $period->id,
            'period_code' => $period->period_code,
            'period_name' => $period->period_name,
            'start_date' => $period->start_date->toDateString(),
            'end_date' => $period->end_date->toDateString(),
            'status' => $period->status,
            'closed_at' => $period->closed_at?->format('d M Y H:i'),
            'payrolls_count' => $period->payrolls_count ?? $period->payrolls()->count(),
            'editable' => $period->isEditable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPayroll(Payroll $payroll): array
    {
        return [
            'id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'employee_code' => $payroll->employee->employee_code,
            'employee_name' => $payroll->employee->full_name,
            'present_days' => $payroll->present_days,
            'late_days' => $payroll->late_days,
            'working_days' => $payroll->working_days,
            'daily_rate' => (float) $payroll->daily_rate,
            'gross_salary' => (float) $payroll->gross_salary,
            'total_deduction' => (float) $payroll->total_deduction,
            'net_salary' => (float) $payroll->net_salary,
            'status' => $payroll->status,
            'details' => $payroll->relationLoaded('details')
                ? $payroll->details->map(fn (PayrollDetail $d) => [
                    'id' => $d->id,
                    'detail_type' => $d->detail_type,
                    'description' => $d->description,
                    'amount' => (float) $d->amount,
                ])->values()
                : [],
        ];
    }
}
