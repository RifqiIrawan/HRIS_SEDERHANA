<?php

namespace Tests\Feature;

use App\Exceptions\AssignmentException;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\User;
use App\Services\ShiftRotationService;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The rotating-shift generator behind the Assignment screen: shift steps down
 * one place per cycle, rest days are spread across the team, and the daily
 * records attendance reads come out of the same run.
 */
class ShiftRotationTest extends TestCase
{
    use RefreshDatabase;

    private ShiftRotationService $rotation;

    private Location $location;

    private Shift $s1;

    private Shift $s2;

    private Shift $s3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rotation = app(ShiftRotationService::class);
        $this->location = Location::factory()->create();

        $this->s1 = Shift::factory()->create([
            'shift_code' => 'S1', 'shift_name' => 'Shift 1',
            'start_time' => '06:00:00', 'end_time' => '14:00:00', 'cross_day' => false,
        ]);

        $this->s2 = Shift::factory()->create([
            'shift_code' => 'S2', 'shift_name' => 'Shift 2',
            'start_time' => '14:00:00', 'end_time' => '22:00:00', 'cross_day' => false,
        ]);

        $this->s3 = Shift::factory()->night()->create(['shift_code' => 'S3', 'shift_name' => 'Shift 3']);
    }

    /**
     * @param  array<int, Employee>  $employees
     * @return array<string, mixed>
     */
    private function generate(
        array $employees,
        string $start,
        string $end,
        int $offDays = 0,
        ?int $startShiftId = null,
        bool $withRoster = true,
        string $offMode = ShiftRotationService::OFF_AUTO,
        array $fixedWeekdays = [],
        ?array $shiftIds = null,
    ): array {
        return $this->rotation->generate(
            employeeIds: collect($employees)->pluck('id')->all(),
            locationId: $this->location->id,
            startDate: Carbon::parse($start),
            endDate: Carbon::parse($end),
            cycleDays: 7,
            direction: ShiftRotationService::DOWN,
            startShiftId: $startShiftId,
            replace: true,
            offDaysPerCycle: $offDays,
            offDayMode: $offMode,
            fixedOffWeekdays: $fixedWeekdays,
            shiftIds: $shiftIds,
            withRoster: $withRoster,
        );
    }

    /**
     * @return array<int, string>
     */
    private function assignedCodes(Employee $employee): array
    {
        return Assignment::with('shift')
            ->where('employee_id', $employee->id)
            ->orderBy('start_date')
            ->get()
            ->map(fn (Assignment $a) => $a->shift->shift_code)
            ->all();
    }

    /* ── The rotation itself ─────────────────────────────────────────── */

    #[Test]
    public function the_shift_steps_down_one_place_each_cycle_and_wraps(): void
    {
        $employee = Employee::factory()->create();

        // The worked example: Monday 1 Aug on shift 3 → 8 Aug on shift 2.
        Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s3->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-07',
            'status' => Assignment::ACTIVE,
        ]);

        $this->generate([$employee], '2026-08-08', '2026-09-04');

        // 1 Aug S3 (existing) then four generated cycles: 2, 1, 3, 2.
        $this->assertSame(['S3', 'S2', 'S1', 'S3', 'S2'], $this->assignedCodes($employee));
    }

    #[Test]
    public function each_generated_assignment_covers_exactly_one_cycle(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-30');

        $ranges = Assignment::where('employee_id', $employee->id)
            ->orderBy('start_date')
            ->get()
            ->map(fn (Assignment $a) => $a->start_date->toDateString().'→'.$a->end_date->toDateString())
            ->all();

        $this->assertSame([
            '2026-08-03→2026-08-09',
            '2026-08-10→2026-08-16',
            '2026-08-17→2026-08-23',
            '2026-08-24→2026-08-30',
        ], $ranges);
    }

    #[Test]
    public function a_range_that_does_not_divide_evenly_ends_on_a_short_cycle(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-13');

        $last = Assignment::where('employee_id', $employee->id)->orderByDesc('start_date')->first();

        $this->assertSame('2026-08-10', $last->start_date->toDateString());
        $this->assertSame('2026-08-13', $last->end_date->toDateString());
    }

    #[Test]
    public function it_continues_from_the_right_point_when_several_cycles_have_passed(): void
    {
        $employee = Employee::factory()->create();

        // Open-ended assignment on S3 from 1 Aug; the rotation resumes three
        // weeks later, so three steps down from S3 lands back on S3.
        Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s3->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'status' => Assignment::ACTIVE,
        ]);

        $this->generate([$employee], '2026-08-22', '2026-08-28');

        $generated = Assignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '2026-08-22')
            ->first();

        $this->assertSame('S3', $generated->shift->shift_code);
    }

    #[Test]
    public function employees_without_an_assignment_are_spread_across_the_shifts(): void
    {
        $employees = collect(range(1, 3))
            ->map(fn (int $n) => Employee::factory()->create(['employee_code' => 'JP0'.$n]))
            ->all();

        $this->generate($employees, '2026-08-03', '2026-08-09');

        $codes = collect($employees)->map(fn (Employee $e) => $this->assignedCodes($e)[0])->all();

        // Every shift manned from day one rather than the whole team on S1.
        $this->assertSame(['S1', 'S2', 'S3'], $codes);
    }

    #[Test]
    public function an_explicit_start_shift_is_used_when_there_is_nothing_to_continue_from(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-16', startShiftId: $this->s2->id);

        $this->assertSame(['S2', 'S1'], $this->assignedCodes($employee));
    }

    #[Test]
    public function only_the_selected_shifts_take_part_in_the_cycle(): void
    {
        $employee = Employee::factory()->create();

        // A two-shift 12-hour pattern while the third shift stays ACTIVE for
        // the history that still references it.
        $this->generate(
            [$employee], '2026-08-03', '2026-08-23',
            startShiftId: $this->s3->id, shiftIds: [$this->s1->id, $this->s3->id],
        );

        $this->assertSame(['S3', 'S1', 'S3'], $this->assignedCodes($employee));
    }

    #[Test]
    public function an_anchor_outside_the_selection_does_not_steer_the_rotation(): void
    {
        $employee = Employee::factory()->create();

        // The employee is on S2, but S2 is not in this cycle — so there is
        // nothing to continue from and the explicit start shift wins.
        Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s2->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-02',
            'status' => Assignment::ACTIVE,
        ]);

        $this->generate(
            [$employee], '2026-08-03', '2026-08-16',
            startShiftId: $this->s3->id, shiftIds: [$this->s1->id, $this->s3->id],
        );

        $codes = Assignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '>=', '2026-08-03')
            ->orderBy('start_date')
            ->get()
            ->map(fn (Assignment $a) => $a->shift->shift_code)
            ->all();

        $this->assertSame(['S3', 'S1'], $codes);
    }

    #[Test]
    public function a_selection_of_one_shift_is_refused(): void
    {
        $employee = Employee::factory()->create();

        $this->expectException(AssignmentException::class);

        $this->generate([$employee], '2026-08-03', '2026-08-09', shiftIds: [$this->s1->id]);
    }

    #[Test]
    public function the_endpoint_rejects_a_single_selected_shift(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('assignments.rotation.generate'), [
                'employee_ids' => [$employee->id],
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-09',
                'cycle_days' => 7,
                'direction' => ShiftRotationService::DOWN,
                'off_days_per_cycle' => 0,
                'off_day_mode' => ShiftRotationService::OFF_AUTO,
                'shift_ids' => [$this->s1->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shift_ids');

        $this->assertSame(0, Assignment::count());
    }

    /* ── Rest days ───────────────────────────────────────────────────── */

    #[Test]
    public function rest_days_fall_on_different_dates_for_different_employees(): void
    {
        $employees = collect(range(1, 3))
            ->map(fn (int $n) => Employee::factory()->create(['employee_code' => 'JP0'.$n]))
            ->all();

        $this->generate($employees, '2026-08-03', '2026-08-09', offDays: 1);

        $offDates = collect($employees)->map(function (Employee $employee) {
            return ShiftRoster::where('employee_id', $employee->id)
                ->where('status', ShiftRoster::OFF)
                ->pluck('roster_date')
                ->map(fn ($date) => $date->toDateString())
                ->all();
        })->all();

        $this->assertSame([['2026-08-03'], ['2026-08-04'], ['2026-08-05']], $offDates);
    }

    #[Test]
    public function two_rest_days_a_cycle_are_spread_rather_than_taken_back_to_back(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-09', offDays: 2);

        $off = ShiftRoster::where('employee_id', $employee->id)
            ->where('status', ShiftRoster::OFF)
            ->orderBy('roster_date')
            ->pluck('roster_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        // Half a cycle apart, so the employee gets a break in each half of
        // the week rather than two rest days running.
        $this->assertSame(['2026-08-03', '2026-08-07'], $off);
    }

    #[Test]
    public function fixed_rest_days_land_on_the_chosen_weekdays_for_everyone(): void
    {
        $employees = collect(range(1, 2))
            ->map(fn (int $n) => Employee::factory()->create(['employee_code' => 'JP0'.$n]))
            ->all();

        // ISO 7 = Sunday.
        $this->generate(
            $employees, '2026-08-03', '2026-08-16',
            offDays: 1, offMode: ShiftRotationService::OFF_FIXED, fixedWeekdays: [7],
        );

        $off = ShiftRoster::where('status', ShiftRoster::OFF)
            ->get()
            ->map(fn (ShiftRoster $r) => $r->roster_date->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['2026-08-09', '2026-08-16'], $off);
        $this->assertSame(4, ShiftRoster::where('status', ShiftRoster::OFF)->count());
    }

    #[Test]
    public function a_rest_day_is_a_roster_row_with_no_shift_or_times(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-09', offDays: 1);

        $roster = ShiftRoster::where('employee_id', $employee->id)
            ->where('status', ShiftRoster::OFF)
            ->first();

        $this->assertNull($roster->shift_id);
        $this->assertNull($roster->start_datetime);
        $this->assertNull($roster->end_datetime);
    }

    #[Test]
    public function the_assignment_still_spans_the_whole_cycle_around_a_rest_day(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-09', offDays: 2);

        $assignment = Assignment::where('employee_id', $employee->id)->sole();

        // The contract covers the cycle; only the daily records mark the rest.
        $this->assertSame('2026-08-03', $assignment->start_date->toDateString());
        $this->assertSame('2026-08-09', $assignment->end_date->toDateString());
        $this->assertSame(5, ShiftRoster::where('employee_id', $employee->id)
            ->where('status', ShiftRoster::SCHEDULED)->count());
    }

    /* ── Daily records ───────────────────────────────────────────────── */

    #[Test]
    public function it_writes_one_daily_record_per_date_with_the_cycles_shift(): void
    {
        $employee = Employee::factory()->create();

        $result = $this->generate([$employee], '2026-08-03', '2026-08-16', startShiftId: $this->s3->id);

        $this->assertSame(14, $result['rosters_created']);

        $codes = ShiftRoster::with('shift')
            ->where('employee_id', $employee->id)
            ->orderBy('roster_date')
            ->get()
            ->map(fn (ShiftRoster $r) => $r->shift->shift_code)
            ->all();

        $this->assertSame(array_merge(array_fill(0, 7, 'S3'), array_fill(0, 7, 'S2')), $codes);
    }

    #[Test]
    public function the_cross_day_night_shift_keeps_its_next_morning_end(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-12', '2026-08-12', startShiftId: $this->s3->id);

        $roster = ShiftRoster::where('employee_id', $employee->id)->sole();

        $this->assertSame('2026-08-12 22:00:00', $roster->start_datetime->toDateTimeString());
        $this->assertSame('2026-08-13 06:00:00', $roster->end_datetime->toDateTimeString());
    }

    #[Test]
    public function a_day_that_already_has_attendance_is_left_alone(): void
    {
        $employee = Employee::factory()->create();

        $roster = ShiftRoster::factory()->create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s1->id,
            'roster_date' => '2026-08-03',
            'start_datetime' => '2026-08-03 06:00:00',
            'end_datetime' => '2026-08-03 14:00:00',
            'status' => ShiftRoster::SCHEDULED,
        ]);

        Attendance::factory()->create([
            'roster_id' => $roster->id,
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'attendance_date' => '2026-08-03',
        ]);

        $result = $this->generate([$employee], '2026-08-03', '2026-08-09', startShiftId: $this->s3->id);

        $this->assertSame(1, $result['rosters_skipped']);
        $this->assertSame($this->s1->id, $roster->fresh()->shift_id);
    }

    #[Test]
    public function the_daily_records_can_be_left_out(): void
    {
        $employee = Employee::factory()->create();

        $result = $this->generate([$employee], '2026-08-03', '2026-08-09', withRoster: false);

        $this->assertSame(1, $result['assignments_created']);
        $this->assertSame(0, ShiftRoster::count());
    }

    /* ── Existing assignments ────────────────────────────────────────── */

    #[Test]
    public function an_open_ended_assignment_is_closed_the_day_before_the_rotation(): void
    {
        $employee = Employee::factory()->create();

        $existing = Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s3->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'status' => Assignment::ACTIVE,
        ]);

        $this->generate([$employee], '2026-08-08', '2026-08-14');

        $this->assertSame('2026-08-07', $existing->fresh()->end_date->toDateString());
    }

    #[Test]
    public function an_assignment_starting_inside_the_range_is_superseded(): void
    {
        $employee = Employee::factory()->create();

        $doomed = Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s1->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-20',
            'status' => Assignment::ACTIVE,
        ]);

        $this->generate([$employee], '2026-08-03', '2026-08-30');

        $this->assertNull(Assignment::find($doomed->id));
    }

    #[Test]
    public function without_replace_a_clashing_employee_is_reported_rather_than_overwritten(): void
    {
        $clashing = Employee::factory()->create(['employee_code' => 'JP01']);
        $clear = Employee::factory()->create(['employee_code' => 'JP02']);

        Assignment::create([
            'employee_id' => $clashing->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s1->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'status' => Assignment::ACTIVE,
        ]);

        $result = $this->rotation->generate(
            employeeIds: [$clashing->id, $clear->id],
            locationId: $this->location->id,
            startDate: Carbon::parse('2026-08-03'),
            endDate: Carbon::parse('2026-08-09'),
            replace: false,
            offDaysPerCycle: 0,
        );

        $this->assertSame(1, $result['employees_done']);
        $this->assertCount(1, Assignment::where('employee_id', $clear->id)->get());
        $this->assertCount(1, Assignment::where('employee_id', $clashing->id)->get());
        $this->assertNotEmpty($result['messages']);
    }

    /* ── Grouped listing ─────────────────────────────────────────────── */

    #[Test]
    public function the_listing_shows_one_row_per_employee_spanning_the_whole_rotation(): void
    {
        $employee = Employee::factory()->create(['employee_code' => 'JP01']);

        $this->generate([$employee], '2026-08-03', '2026-08-30', startShiftId: $this->s3->id);

        $this->assertSame(4, Assignment::count());

        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            // The shift shown is the one the rotation opens on…
            ->assertJsonPath('data.items.0.shift_code', 'S3')
            // …and the dates span every cycle, not just the first.
            ->assertJsonPath('data.items.0.start_date', '2026-08-03')
            ->assertJsonPath('data.items.0.end_date', '2026-08-30')
            ->assertJsonPath('data.items.0.cycles', 4)
            ->assertJsonPath('data.items.0.rotation', ['S3', 'S2', 'S1']);
    }

    #[Test]
    public function each_employee_gets_their_own_row(): void
    {
        $employees = collect(range(1, 3))
            ->map(fn (int $n) => Employee::factory()->create(['employee_code' => 'JP0'.$n]))
            ->all();

        $this->generate($employees, '2026-08-03', '2026-08-16');

        $this->assertSame(6, Assignment::count());

        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.items.0.employee_code', 'JP01')
            ->assertJsonPath('data.items.0.cycles', 2);
    }

    /**
     * An open-ended cycle has no end, so neither does the run containing it —
     * quoting the latest closed cycle instead would understate the coverage.
     */
    #[Test]
    public function an_open_ended_cycle_leaves_the_rotation_end_empty(): void
    {
        $employee = Employee::factory()->create();

        Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s1->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-09',
            'status' => Assignment::ACTIVE,
        ]);

        Assignment::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'shift_id' => $this->s2->id,
            'start_date' => '2026-08-10',
            'end_date' => null,
            'status' => Assignment::ACTIVE,
        ]);

        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.index'))
            ->assertOk()
            ->assertJsonPath('data.items.0.start_date', '2026-08-03')
            ->assertJsonPath('data.items.0.end_date', null);
    }

    #[Test]
    public function a_shift_filter_narrows_the_summary_to_the_matching_cycles(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-30', startShiftId: $this->s3->id);

        // Only the S1 cycle survives the filter, so the row describes that one.
        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.index').'?shift_id='.$this->s1->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.cycles', 1)
            ->assertJsonPath('data.items.0.shift_code', 'S1')
            ->assertJsonPath('data.items.0.start_date', '2026-08-17');
    }

    #[Test]
    public function an_employee_with_no_assignments_is_not_listed(): void
    {
        Employee::factory()->create(['employee_code' => 'JP99']);

        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    #[Test]
    public function deleting_a_row_removes_every_cycle_behind_it(): void
    {
        $kept = Employee::factory()->create(['employee_code' => 'JP01']);
        $removed = Employee::factory()->create(['employee_code' => 'JP02']);

        $this->generate([$kept, $removed], '2026-08-03', '2026-08-30');

        $this->assertSame(8, Assignment::count());

        $this->actingAs($this->adminUser())
            ->deleteJson(route('assignments.employee.destroy', $removed))
            ->assertOk()
            ->assertJsonPath('data.assignments', 4)
            ->assertJsonPath('data.rosters', 28);

        $this->assertSame(0, Assignment::where('employee_id', $removed->id)->count());
        $this->assertSame(0, ShiftRoster::where('employee_id', $removed->id)->count());

        // Only that employee: the other row on screen is untouched.
        $this->assertSame(4, Assignment::where('employee_id', $kept->id)->count());
        $this->assertSame(28, ShiftRoster::where('employee_id', $kept->id)->count());
    }

    /**
     * attendances.roster_id is nullOnDelete, so deleting a clocked-in day would
     * succeed and quietly orphan the attendance rather than fail loudly. That
     * is exactly why the day has to be kept.
     */
    #[Test]
    public function a_day_with_attendance_survives_the_delete_and_is_reported(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-09', startShiftId: $this->s1->id);

        $roster = ShiftRoster::where('employee_id', $employee->id)
            ->whereDate('roster_date', '2026-08-05')
            ->sole();

        $attendance = Attendance::factory()->create([
            'roster_id' => $roster->id,
            'employee_id' => $employee->id,
            'location_id' => $this->location->id,
            'attendance_date' => '2026-08-05',
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(route('assignments.employee.destroy', $employee))
            ->assertOk()
            ->assertJsonPath('data.rosters', 6)
            ->assertJsonPath('data.kept_with_attendance', 1);

        $this->assertNotNull(ShiftRoster::find($roster->id));
        $this->assertSame($roster->id, $attendance->fresh()->roster_id);
        $this->assertSame(1, ShiftRoster::where('employee_id', $employee->id)->count());
    }

    /* ── HTTP ────────────────────────────────────────────────────────── */

    #[Test]
    public function the_preview_endpoint_writes_nothing(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('assignments.rotation.preview'), [
                'employee_ids' => [$employee->id],
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-16',
                'cycle_days' => 7,
                'direction' => ShiftRotationService::DOWN,
                'off_days_per_cycle' => 1,
                'off_day_mode' => ShiftRotationService::OFF_AUTO,
            ])
            ->assertOk()
            ->assertJsonPath('data.employees.0.blocks.0.shift_code', 'S1');

        $this->assertSame(0, Assignment::count());
        $this->assertSame(0, ShiftRoster::count());
    }

    #[Test]
    public function the_generate_endpoint_writes_both_the_assignments_and_the_daily_records(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('assignments.rotation.generate'), [
                'employee_ids' => [$employee->id],
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-16',
                'cycle_days' => 7,
                'direction' => ShiftRotationService::DOWN,
                'off_days_per_cycle' => 1,
                'off_day_mode' => ShiftRotationService::OFF_AUTO,
                'replace' => 1,
                'with_roster' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assignments_created', 2)
            ->assertJsonPath('data.rosters_created', 14);

        $this->assertSame(2, Assignment::count());
        $this->assertSame(2, ShiftRoster::where('status', ShiftRoster::OFF)->count());
    }

    #[Test]
    public function the_generator_is_closed_to_roles_without_the_assignment_menu(): void
    {
        $employee = Employee::factory()->create();
        $this->adminUser();

        $this->actingAs(User::factory()->role(Role::EMPLOYEE)->create())
            ->postJson(route('assignments.rotation.generate'), [
                'employee_ids' => [$employee->id],
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-09',
            ])
            ->assertForbidden();

        $this->assertSame(0, Assignment::count());
    }

    #[Test]
    public function the_detail_endpoint_returns_both_the_periods_and_the_daily_schedule(): void
    {
        $employee = Employee::factory()->create();

        $this->generate([$employee], '2026-08-03', '2026-08-16', offDays: 1, startShiftId: $this->s3->id);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('assignments.employee-shifts', $employee).'?start_date=2026-08-01&end_date=2026-08-31')
            ->assertOk()
            ->assertJsonPath('data.employee.code', $employee->employee_code)
            ->assertJsonPath('data.summary.scheduled', 12)
            ->assertJsonPath('data.summary.off', 2)
            ->assertJsonCount(2, 'data.assignments')
            ->assertJsonCount(14, 'data.rosters');

        // Row 0 is this employee's rest day, so it carries no shift or hours.
        $this->assertSame('OFF', $response->json('data.rosters.0.status'));
        $this->assertNull($response->json('data.rosters.0.shift_code'));

        // The night shift's end carries its date, or 06:00 reads as before its
        // own 22:00 start.
        $this->assertSame('S3', $response->json('data.rosters.1.shift_code'));
        $this->assertSame('22:00', $response->json('data.rosters.1.start'));
        $this->assertStringContainsString('06:00', $response->json('data.rosters.1.end'));
    }

    #[Test]
    public function the_detail_window_is_capped_rather_than_returning_a_year_of_rows(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->adminUser())
            ->getJson(route('assignments.employee-shifts', $employee).'?start_date=2026-08-01&end_date=2027-07-31')
            ->assertOk()
            ->assertJsonPath('data.range.end', '2026-11-01');
    }

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $this->seed(MenuSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('role_code', Role::ADMIN)->value('id'),
        ]);
    }
}
