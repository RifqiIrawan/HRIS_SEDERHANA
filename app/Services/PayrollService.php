<?php

namespace App\Services;

use App\Exceptions\PayrollException;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Spec §35, §38, §39 — daily-rate payroll.
 *
 *   working_days = PRESENT + LATE attendances inside the period
 *   gross        = working_days × daily_rate
 *   net          = gross − manual deductions
 *
 * There is no tax, BPJS or proration engine in the MVP by design (spec §3).
 */
class PayrollService
{
    /**
     * Spec §38. Recalculates every ACTIVE employee's line for the period and
     * marks the period PROCESSED. Existing deduction lines are preserved, so
     * re-running after a late attendance correction keeps HR's manual entries.
     *
     * @return array{employees: int, working_days: int, gross: string, deduction: string, net: string}
     */
    public function generate(PayrollPeriod $period): array
    {
        if ($period->isClosed()) {
            throw PayrollException::periodClosed();
        }

        $start = $period->start_date->toDateString();
        $end = $period->end_date->toDateString();

        // One grouped query for the whole period instead of two per employee.
        $counts = Attendance::query()
            ->selectRaw('employee_id, status, COUNT(*) as total')
            ->payable()
            ->betweenDates($start, $end)
            ->groupBy('employee_id', 'status')
            ->get()
            ->groupBy('employee_id');

        $employees = Employee::active()->orderBy('employee_code')->get();

        DB::transaction(function () use ($period, $employees, $counts) {
            foreach ($employees as $employee) {
                $rows = $counts->get($employee->id, collect());

                $presentDays = (int) ($rows->firstWhere('status', Attendance::PRESENT)->total ?? 0);
                $lateDays = (int) ($rows->firstWhere('status', Attendance::LATE)->total ?? 0);
                $workingDays = $presentDays + $lateDays;

                // Snapshot the rate as it stands now; a later raise must not
                // rewrite a payslip that has already been generated.
                $dailyRate = (float) $employee->daily_rate;
                $gross = round($workingDays * $dailyRate, 2);

                $payroll = Payroll::firstOrNew([
                    'period_id' => $period->id,
                    'employee_id' => $employee->id,
                ]);

                $payroll->fill([
                    'present_days' => $presentDays,
                    'late_days' => $lateDays,
                    'working_days' => $workingDays,
                    'daily_rate' => $dailyRate,
                    'gross_salary' => $gross,
                    'status' => Payroll::DRAFT,
                ])->save();

                $this->recalculate($payroll);
            }

            $period->update(['status' => PayrollPeriod::PROCESSED]);
        });

        $summary = $this->summary($period);

        AuditLog::record('payroll.generate', $period, sprintf(
            'Generate payroll periode %s (%d karyawan, net %s)',
            $period->period_name,
            $summary['employees'],
            number_format((float) $summary['net'], 0, ',', '.'),
        ), $summary);

        return $summary;
    }

    /**
     * PAY-011 — net = gross − deductions. Called after any change to the
     * deduction lines.
     */
    public function recalculate(Payroll $payroll): Payroll
    {
        $deduction = (float) $payroll->details()
            ->where('detail_type', PayrollDetail::DEDUCTION)
            ->sum('amount');

        $gross = (float) $payroll->gross_salary;

        $payroll->forceFill([
            'total_deduction' => round($deduction, 2),
            'net_salary' => round($gross - $deduction, 2),
        ])->save();

        return $payroll;
    }

    /**
     * Spec §42 — manual deduction lines only (Kasbon, Denda, Potongan Lain).
     */
    public function addDeduction(Payroll $payroll, string $description, float $amount): PayrollDetail
    {
        $this->assertEditable($payroll->period);

        $detail = $payroll->details()->create([
            'detail_type' => PayrollDetail::DEDUCTION,
            'description' => $description,
            'amount' => round($amount, 2),
        ]);

        $this->recalculate($payroll);

        AuditLog::record('payroll.deduction_added', $payroll, sprintf(
            'Potongan "%s" sebesar %s',
            $description,
            number_format($amount, 0, ',', '.'),
        ));

        return $detail;
    }

    public function removeDetail(PayrollDetail $detail): void
    {
        $payroll = $detail->payroll;

        $this->assertEditable($payroll->period);

        $detail->delete();
        $this->recalculate($payroll);

        AuditLog::record('payroll.deduction_removed', $payroll, sprintf(
            'Potongan "%s" dihapus',
            $detail->description,
        ));
    }

    /**
     * Spec §44 — PROCESSED → CLOSED. Lines are stamped FINAL so a later report
     * can tell a frozen payslip from a draft one.
     */
    public function close(PayrollPeriod $period): PayrollPeriod
    {
        if ($period->isClosed()) {
            throw PayrollException::periodClosed();
        }

        if ($period->status !== PayrollPeriod::PROCESSED) {
            throw PayrollException::periodNotProcessed();
        }

        DB::transaction(function () use ($period) {
            $period->payrolls()->update(['status' => Payroll::FINAL]);
            $period->update([
                'status' => PayrollPeriod::CLOSED,
                'closed_at' => Carbon::now(),
            ]);
        });

        AuditLog::record('payroll.close', $period, sprintf('Periode %s ditutup', $period->period_name));

        return $period->refresh();
    }

    /**
     * Spec §44 — ADMIN-only escape hatch back to PROCESSED.
     */
    public function reopen(PayrollPeriod $period): PayrollPeriod
    {
        if (! $period->isClosed()) {
            throw PayrollException::periodNotClosed();
        }

        DB::transaction(function () use ($period) {
            $period->payrolls()->update(['status' => Payroll::DRAFT]);
            $period->update([
                'status' => PayrollPeriod::PROCESSED,
                'closed_at' => null,
            ]);
        });

        AuditLog::record('payroll.reopen', $period, sprintf('Periode %s dibuka kembali', $period->period_name));

        return $period->refresh();
    }

    /**
     * @return array{employees: int, working_days: int, gross: string, deduction: string, net: string}
     */
    public function summary(PayrollPeriod $period): array
    {
        $row = $period->payrolls()
            ->selectRaw('COUNT(*) as employees')
            ->selectRaw('COALESCE(SUM(working_days), 0) as working_days')
            ->selectRaw('COALESCE(SUM(gross_salary), 0) as gross')
            ->selectRaw('COALESCE(SUM(total_deduction), 0) as deduction')
            ->selectRaw('COALESCE(SUM(net_salary), 0) as net')
            ->first();

        return [
            'employees' => (int) $row->employees,
            'working_days' => (int) $row->working_days,
            'gross' => (string) $row->gross,
            'deduction' => (string) $row->deduction,
            'net' => (string) $row->net,
        ];
    }

    private function assertEditable(PayrollPeriod $period): void
    {
        if ($period->isClosed()) {
            throw PayrollException::periodClosed();
        }
    }
}
