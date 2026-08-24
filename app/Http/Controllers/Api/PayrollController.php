<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Slip gaji saya".
 *
 * The web PayrollController is HR-facing: its index answers with every
 * employee's line for a period, and it can generate, close and deduct. None of
 * that may be reachable from a phone, so this is a separate read-only surface
 * with a single hard rule — the query always starts from the token's employee.
 *
 * Only FINAL lines are published. A DRAFT payslip still moves: HR can add a
 * deduction or regenerate the period, and an employee who screenshots a figure
 * that later changes will read it as the company altering their pay. A slip
 * becomes visible at the moment PayrollService::close() freezes it (spec §44).
 */
class PayrollController extends Controller
{
    use ResolvesEmployee;

    public function index(Request $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);

        $payrolls = Payroll::query()
            ->with('period:id,period_code,period_name,start_date,end_date,status')
            // Ordering newest-first means ordering by the period's start date,
            // which lives on the other table — hence the join. Every column below
            // is qualified because `status` exists on both sides and MySQL will
            // not guess which one was meant.
            ->join('payroll_periods', 'payrolls.period_id', '=', 'payroll_periods.id')
            ->where('payrolls.employee_id', $employee->id)
            ->where('payrolls.status', Payroll::FINAL)
            ->orderByDesc('payroll_periods.start_date')
            ->select('payrolls.*')
            ->paginate($this->perPage($request));

        return $this->paginated($payrolls, fn (Payroll $payroll) => $this->transform($payroll));
    }

    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $employee = $this->currentEmployee($request);

        // Route-model binding will happily resolve someone else's slip, so the
        // ownership check is the authorisation — not the route.
        if ($payroll->employee_id !== $employee->id) {
            return $this->fail('Anda tidak memiliki akses ke slip gaji ini.', 403);
        }

        if ($payroll->status !== Payroll::FINAL) {
            return $this->fail(
                'Slip gaji periode ini belum final.',
                422,
                'PAYROLL_NOT_FINAL',
            );
        }

        return $this->ok($this->transform(
            $payroll->load(['period', 'details']),
            detailed: true,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Payroll $payroll, bool $detailed = false): array
    {
        $period = $payroll->period;

        $data = [
            'id' => $payroll->id,
            'period_code' => $period?->period_code,
            'period_name' => $period?->period_name,
            'start_date' => $period?->start_date?->toDateString(),
            'end_date' => $period?->end_date?->toDateString(),
            'present_days' => $payroll->present_days,
            'late_days' => $payroll->late_days,
            'working_days' => $payroll->working_days,
            'daily_rate' => (float) $payroll->daily_rate,
            'gross_salary' => (float) $payroll->gross_salary,
            'total_deduction' => (float) $payroll->total_deduction,
            'net_salary' => (float) $payroll->net_salary,
            'status' => $payroll->status,
        ];

        if (! $detailed) {
            return $data;
        }

        return $data + [
            'deductions' => $payroll->details
                ->where('detail_type', PayrollDetail::DEDUCTION)
                ->map(fn (PayrollDetail $detail) => [
                    'id' => $detail->id,
                    'description' => $detail->description,
                    'amount' => (float) $detail->amount,
                ])->values(),
        ];
    }
}
