<?php

namespace Tests\Feature;

use App\Exceptions\PayrollException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Spec §58 — payroll acceptance criteria PAY-001 … PAY-013, and the worked
 * example from spec §59 (25 × 150.000 − 200.000 = 3.550.000).
 */
class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payroll;

    private PayrollPeriod $period;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 8, 31, 17, 0));

        $this->payroll = app(PayrollService::class);
        $this->location = Location::factory()->create();

        $this->period = PayrollPeriod::factory()->create([
            'period_code' => '2026-08',
            'period_name' => 'Agustus 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Writes `$count` attendance days of the given status, starting from
     * `$startDay` of August so days never collide on the unique index.
     */
    private function attendances(Employee $employee, string $status, int $count, int $startDay = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::create(2026, 8, $startDay + $i);

            Attendance::factory()
                ->on($date)
                ->status($status)
                ->create([
                    'employee_id' => $employee->id,
                    'location_id' => $this->location->id,
                    'late_minutes' => $status === Attendance::LATE ? 20 : 0,
                ]);
        }
    }

    /* ── PAY-001 ─────────────────────────────────────────────────────── */

    #[Test]
    public function pay001_hr_can_create_a_payroll_period(): void
    {
        $this->actingAs(User::factory()->hr()->create())
            ->postJson(route('payroll.periods.store'), [
                'period_code' => '2026-09',
                'period_name' => 'September 2026',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('payroll_periods', [
            'period_code' => '2026-09',
            'status' => PayrollPeriod::OPEN,
        ]);
    }

    #[Test]
    public function overlapping_periods_are_refused(): void
    {
        // Two periods covering the same day would pay it twice.
        $this->actingAs(User::factory()->hr()->create())
            ->postJson(route('payroll.periods.store'), [
                'period_code' => '2026-08b',
                'period_name' => 'Agustus 2026 (duplikat)',
                'start_date' => '2026-08-15',
                'end_date' => '2026-09-15',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    /* ── PAY-002 … PAY-009 ───────────────────────────────────────────── */

    #[Test]
    public function pay003_to_pay009_working_days_come_from_valid_attendance(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();

        // Spec §39 worked example: 20 PRESENT + 5 LATE + 2 ABSENT + 4 OFF.
        $this->attendances($employee, Attendance::PRESENT, 20, startDay: 1);
        $this->attendances($employee, Attendance::LATE, 5, startDay: 21);
        $this->attendances($employee, Attendance::ABSENT, 2, startDay: 26);
        // OFF days are simply absent from the attendance table.

        $this->payroll->generate($this->period);

        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $this->assertSame(20, $payroll->present_days);       // PAY-004
        $this->assertSame(5, $payroll->late_days);           // PAY-005
        $this->assertSame(25, $payroll->working_days);       // PAY-003, PAY-006, PAY-007
        $this->assertSame('150000.00', $payroll->daily_rate); // PAY-008
        $this->assertSame('3750000.00', $payroll->gross_salary); // PAY-009
    }

    #[Test]
    public function pay007_incomplete_days_are_not_paid(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();

        $this->attendances($employee, Attendance::PRESENT, 3, startDay: 1);

        // Checked in but never checked out — not a completed working day.
        Attendance::factory()
            ->on(Carbon::create(2026, 8, 10))
            ->incomplete()
            ->create(['employee_id' => $employee->id, 'location_id' => $this->location->id]);

        $this->payroll->generate($this->period);

        $this->assertSame(3, Payroll::where('employee_id', $employee->id)->first()->working_days);
    }

    #[Test]
    public function attendance_outside_the_period_is_not_counted(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();

        $this->attendances($employee, Attendance::PRESENT, 2, startDay: 1);

        Attendance::factory()
            ->on(Carbon::create(2026, 7, 20))
            ->create(['employee_id' => $employee->id, 'location_id' => $this->location->id]);

        $this->payroll->generate($this->period);

        $this->assertSame(2, Payroll::where('employee_id', $employee->id)->first()->working_days);
    }

    #[Test]
    public function pay002_only_active_employees_are_paid(): void
    {
        $active = Employee::factory()->create();
        $inactive = Employee::factory()->inactive()->create();

        $this->attendances($active, Attendance::PRESENT, 2, startDay: 1);
        $this->attendances($inactive, Attendance::PRESENT, 2, startDay: 1);

        $this->payroll->generate($this->period);

        $this->assertDatabaseHas('payrolls', ['employee_id' => $active->id]);
        $this->assertDatabaseMissing('payrolls', ['employee_id' => $inactive->id]);
    }

    /* ── PAY-010 / PAY-011 ───────────────────────────────────────────── */

    #[Test]
    public function pay010_and_pay011_deductions_reduce_the_net_salary(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();
        $this->attendances($employee, Attendance::PRESENT, 25, startDay: 1);

        $this->payroll->generate($this->period);
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $this->payroll->addDeduction($payroll, 'Kasbon', 200000);

        $payroll->refresh();

        // Spec §35 / §59 worked example.
        $this->assertSame('3750000.00', $payroll->gross_salary);
        $this->assertSame('200000.00', $payroll->total_deduction);
        $this->assertSame('3550000.00', $payroll->net_salary);
    }

    #[Test]
    public function multiple_deductions_accumulate(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();
        $this->attendances($employee, Attendance::PRESENT, 25, startDay: 1);

        $this->payroll->generate($this->period);
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        // Spec §41 example lines.
        $this->payroll->addDeduction($payroll, 'Kasbon', 200000);
        $this->payroll->addDeduction($payroll, 'Denda', 50000);

        $payroll->refresh();

        $this->assertSame('250000.00', $payroll->total_deduction);
        $this->assertSame('3500000.00', $payroll->net_salary);
    }

    #[Test]
    public function removing_a_deduction_restores_the_net_salary(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();
        $this->attendances($employee, Attendance::PRESENT, 10, startDay: 1);

        $this->payroll->generate($this->period);
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $detail = $this->payroll->addDeduction($payroll, 'Denda', 75000);
        $this->assertSame('1425000.00', $payroll->refresh()->net_salary);

        $this->payroll->removeDetail($detail);

        $this->assertSame('0.00', $payroll->refresh()->total_deduction);
        $this->assertSame('1500000.00', $payroll->net_salary);
    }

    #[Test]
    public function regenerating_keeps_manual_deductions(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();
        $this->attendances($employee, Attendance::PRESENT, 10, startDay: 1);

        $this->payroll->generate($this->period);
        $payroll = Payroll::where('employee_id', $employee->id)->first();
        $this->payroll->addDeduction($payroll, 'Kasbon', 100000);

        // A late attendance correction arrives, then payroll is re-run.
        $this->attendances($employee, Attendance::LATE, 2, startDay: 20);
        $this->payroll->generate($this->period);

        $payroll->refresh();

        $this->assertSame(12, $payroll->working_days);
        $this->assertSame('1800000.00', $payroll->gross_salary);
        $this->assertSame('100000.00', $payroll->total_deduction);
        $this->assertSame('1700000.00', $payroll->net_salary);
        $this->assertCount(1, $payroll->details);
    }

    /* ── PAY-012 / PAY-013 ───────────────────────────────────────────── */

    #[Test]
    public function pay012_a_processed_period_can_be_closed(): void
    {
        Employee::factory()->create();

        $this->payroll->generate($this->period);
        $this->assertSame(PayrollPeriod::PROCESSED, $this->period->refresh()->status);

        $this->payroll->close($this->period);

        $this->period->refresh();
        $this->assertSame(PayrollPeriod::CLOSED, $this->period->status);
        $this->assertNotNull($this->period->closed_at);
        $this->assertSame(Payroll::FINAL, $this->period->payrolls()->first()->status);
    }

    #[Test]
    public function a_period_cannot_be_closed_before_it_is_generated(): void
    {
        $this->expectException(PayrollException::class);

        $this->payroll->close($this->period);
    }

    #[Test]
    public function pay013_a_closed_period_cannot_be_regenerated(): void
    {
        Employee::factory()->create();
        $this->payroll->generate($this->period);
        $this->payroll->close($this->period);

        $this->expectException(PayrollException::class);

        $this->payroll->generate($this->period->refresh());
    }

    #[Test]
    public function pay013_a_closed_period_rejects_new_deductions(): void
    {
        $employee = Employee::factory()->create();
        $this->payroll->generate($this->period);
        $this->payroll->close($this->period);

        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $this->expectException(PayrollException::class);

        $this->payroll->addDeduction($payroll, 'Kasbon', 50000);
    }

    #[Test]
    public function an_admin_can_reopen_a_closed_period(): void
    {
        Employee::factory()->create();
        $this->payroll->generate($this->period);
        $this->payroll->close($this->period);

        $this->payroll->reopen($this->period->refresh());

        $this->period->refresh();
        $this->assertSame(PayrollPeriod::PROCESSED, $this->period->status);
        $this->assertNull($this->period->closed_at);
        $this->assertSame(Payroll::DRAFT, $this->period->payrolls()->first()->status);

        // And regeneration works again afterwards.
        $this->payroll->generate($this->period);
        $this->assertSame(PayrollPeriod::PROCESSED, $this->period->refresh()->status);
    }

    #[Test]
    public function reopen_is_refused_to_hr(): void
    {
        Employee::factory()->create();
        $this->payroll->generate($this->period);
        $this->payroll->close($this->period);

        $this->actingAs(User::factory()->hr()->create())
            ->postJson(route('payroll.reopen', $this->period))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('payroll.reopen', $this->period))
            ->assertOk();
    }

    /* ── Totals ──────────────────────────────────────────────────────── */

    #[Test]
    public function the_period_summary_matches_the_spec_example(): void
    {
        // Spec §43: Budi 25×150K, Andi 24×175K, Joko 26×150K.
        $budi = Employee::factory()->dailyRate(150000)->create(['employee_code' => 'JP001']);
        $andi = Employee::factory()->dailyRate(175000)->create(['employee_code' => 'JP002']);
        $joko = Employee::factory()->dailyRate(150000)->create(['employee_code' => 'JP003']);

        $this->attendances($budi, Attendance::PRESENT, 25, startDay: 1);
        $this->attendances($andi, Attendance::PRESENT, 24, startDay: 1);
        $this->attendances($joko, Attendance::PRESENT, 26, startDay: 1);

        $this->payroll->generate($this->period);

        $payrolls = Payroll::with('employee')->get()->keyBy(fn ($p) => $p->employee->employee_code);

        $this->payroll->addDeduction($payrolls['JP001'], 'Kasbon', 200000);
        $this->payroll->addDeduction($payrolls['JP002'], 'Kasbon', 100000);

        $summary = $this->payroll->summary($this->period->refresh());

        $this->assertSame(3, $summary['employees']);
        $this->assertSame(75, $summary['working_days']);
        $this->assertSame('11850000.00', $summary['gross']);
        $this->assertSame('300000.00', $summary['deduction']);
        $this->assertSame('11550000.00', $summary['net']);
    }

    #[Test]
    public function an_employee_with_no_attendance_gets_a_zero_line(): void
    {
        $employee = Employee::factory()->dailyRate(150000)->create();

        $this->payroll->generate($this->period);

        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $this->assertNotNull($payroll, 'Setiap karyawan aktif tetap mendapat baris payroll.');
        $this->assertSame(0, $payroll->working_days);
        $this->assertSame('0.00', $payroll->gross_salary);
        $this->assertSame('0.00', $payroll->net_salary);
    }

    #[Test]
    public function deleting_a_payroll_detail_cascades_from_the_period(): void
    {
        $employee = Employee::factory()->create();
        $this->payroll->generate($this->period);

        $payroll = Payroll::where('employee_id', $employee->id)->first();
        $this->payroll->addDeduction($payroll, 'Kasbon', 50000);

        $this->assertDatabaseCount('payroll_details', 1);

        $this->period->delete();

        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('payroll_details', 0);
    }

    #[Test]
    public function payroll_detail_amounts_are_validated(): void
    {
        $employee = Employee::factory()->create();
        $this->payroll->generate($this->period);
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $this->actingAs(User::factory()->hr()->create())
            ->postJson(route('payroll.deduction', $payroll), ['description' => '', 'amount' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description', 'amount']);

        $this->assertDatabaseCount('payroll_details', 0);
    }
}
