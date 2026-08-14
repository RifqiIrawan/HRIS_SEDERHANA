<?php

namespace Tests\Feature;

use App\Exceptions\RosterException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Services\RosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Spec §16 & §17 — pattern generation and the cross-day boundary.
 */
class RosterGenerationTest extends TestCase
{
    use RefreshDatabase;

    private RosterService $rosters;

    private Employee $employee;

    private Location $location;

    private Shift $s1;

    private Shift $s2;

    private Shift $s3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rosters = app(RosterService::class);

        $this->employee = Employee::factory()->create();
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
     * @param  array<int, string>  $pattern
     * @return array<string, mixed>
     */
    private function generate(array $pattern, string $start, string $end, bool $overwrite = false): array
    {
        return $this->rosters->generate(
            employeeIds: [$this->employee->id],
            locationId: $this->location->id,
            startDate: Carbon::parse($start),
            endDate: Carbon::parse($end),
            pattern: $pattern,
            overwrite: $overwrite,
        );
    }

    #[Test]
    public function it_cycles_the_pattern_across_the_range(): void
    {
        // Spec §16 worked example: 1,2,3,OFF from 01 Aug.
        $result = $this->generate(['S1', 'S2', 'S3', 'OFF'], '2026-08-01', '2026-08-08');

        $this->assertSame(8, $result['created']);
        $this->assertSame(8, $result['days']);

        $expected = [
            '2026-08-01' => 'S1',
            '2026-08-02' => 'S2',
            '2026-08-03' => 'S3',
            '2026-08-04' => null,
            '2026-08-05' => 'S1',
            '2026-08-06' => 'S2',
            '2026-08-07' => 'S3',
            '2026-08-08' => null,
        ];

        $rosters = ShiftRoster::with('shift')->get()
            ->keyBy(fn (ShiftRoster $r) => $r->roster_date->toDateString());

        foreach ($expected as $date => $code) {
            $this->assertSame($code, $rosters[$date]->shift?->shift_code, "Tanggal {$date}");
        }
    }

    #[Test]
    public function an_uneven_pattern_also_cycles_correctly(): void
    {
        // Spec §16 second example: 1,1,2,2,3,3,OFF.
        $this->generate(['S1', 'S1', 'S2', 'S2', 'S3', 'S3', 'OFF'], '2026-08-01', '2026-08-09');

        $codes = ShiftRoster::with('shift')->orderBy('roster_date')->get()
            ->map(fn (ShiftRoster $r) => $r->shift?->shift_code ?? 'OFF')
            ->all();

        $this->assertSame(['S1', 'S1', 'S2', 'S2', 'S3', 'S3', 'OFF', 'S1', 'S1'], $codes);
    }

    #[Test]
    public function an_off_token_produces_a_row_with_no_shift_or_times(): void
    {
        $this->generate(['OFF'], '2026-08-04', '2026-08-04');

        $roster = ShiftRoster::first();

        $this->assertSame(ShiftRoster::OFF, $roster->status);
        $this->assertNull($roster->shift_id);
        $this->assertNull($roster->start_datetime);
        $this->assertNull($roster->end_datetime);
        $this->assertFalse($roster->isWorkingDay());
    }

    /* ── Spec §17 ────────────────────────────────────────────────────── */

    #[Test]
    public function a_cross_day_shift_ends_on_the_following_calendar_day(): void
    {
        $this->generate(['S3'], '2026-08-12', '2026-08-12');

        $roster = ShiftRoster::first();

        // Spec §17 verbatim: start 12 Aug 22:00, end 13 Aug 06:00.
        $this->assertSame('2026-08-12 22:00:00', $roster->start_datetime->toDateTimeString());
        $this->assertSame('2026-08-13 06:00:00', $roster->end_datetime->toDateTimeString());
        $this->assertSame('2026-08-12', $roster->roster_date->toDateString());
    }

    #[Test]
    public function a_same_day_shift_starts_and_ends_on_the_roster_date(): void
    {
        $this->generate(['S1'], '2026-08-12', '2026-08-12');

        $roster = ShiftRoster::first();

        $this->assertSame('2026-08-12 06:00:00', $roster->start_datetime->toDateTimeString());
        $this->assertSame('2026-08-12 14:00:00', $roster->end_datetime->toDateTimeString());
    }

    #[Test]
    public function consecutive_night_shifts_do_not_overlap_each_other(): void
    {
        $this->generate(['S3'], '2026-08-12', '2026-08-14');

        $rosters = ShiftRoster::orderBy('roster_date')->get();

        $this->assertSame('2026-08-13 06:00:00', $rosters[0]->end_datetime->toDateTimeString());
        $this->assertSame('2026-08-13 22:00:00', $rosters[1]->start_datetime->toDateTimeString());
        $this->assertTrue($rosters[1]->start_datetime->greaterThan($rosters[0]->end_datetime));
    }

    /* ── Overwrite behaviour ─────────────────────────────────────────── */

    #[Test]
    public function existing_days_are_skipped_unless_overwrite_is_requested(): void
    {
        $this->generate(['S1'], '2026-08-01', '2026-08-03');

        $result = $this->generate(['S2'], '2026-08-01', '2026-08-03');

        $this->assertSame(0, $result['created']);
        $this->assertSame(3, $result['skipped']);
        $this->assertSame($this->s1->id, ShiftRoster::first()->shift_id);
    }

    #[Test]
    public function overwrite_replaces_the_shift_and_recomputes_the_times(): void
    {
        $this->generate(['S1'], '2026-08-12', '2026-08-12');

        $result = $this->generate(['S3'], '2026-08-12', '2026-08-12', overwrite: true);

        $this->assertSame(1, $result['updated']);

        $roster = ShiftRoster::first();
        $this->assertSame($this->s3->id, $roster->shift_id);
        $this->assertSame('2026-08-13 06:00:00', $roster->end_datetime->toDateTimeString());
    }

    #[Test]
    public function a_day_that_already_has_attendance_is_never_overwritten(): void
    {
        $this->generate(['S1'], '2026-08-12', '2026-08-12');
        $roster = ShiftRoster::first();

        Attendance::factory()->on(Carbon::create(2026, 8, 12))->create([
            'employee_id' => $this->employee->id,
            'location_id' => $this->location->id,
            'roster_id' => $roster->id,
        ]);

        $result = $this->generate(['S3'], '2026-08-12', '2026-08-12', overwrite: true);

        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['messages']);
        $this->assertSame($this->s1->id, $roster->refresh()->shift_id);
    }

    /* ── Validation ──────────────────────────────────────────────────── */

    #[Test]
    public function an_unknown_shift_code_aborts_before_writing_anything(): void
    {
        try {
            $this->generate(['S1', 'TIDAK_ADA'], '2026-08-01', '2026-08-30');
            $this->fail('Kode shift tidak dikenal seharusnya ditolak.');
        } catch (RosterException $e) {
            $this->assertSame('UNKNOWN_SHIFT_CODE', $e->errorCode);
        }

        // Nothing partially written for the valid half of the pattern.
        $this->assertDatabaseCount('shift_rosters', 0);
    }

    #[Test]
    public function an_inactive_shift_cannot_be_rostered(): void
    {
        $this->s2->update(['status' => Shift::INACTIVE]);

        $this->expectException(RosterException::class);

        $this->generate(['S2'], '2026-08-01', '2026-08-01');
    }

    #[Test]
    public function an_inactive_location_cannot_be_rostered(): void
    {
        $this->location->update(['status' => Location::INACTIVE]);

        $this->expectException(RosterException::class);

        $this->generate(['S1'], '2026-08-01', '2026-08-01');
    }

    #[Test]
    public function an_empty_pattern_is_refused(): void
    {
        $this->expectException(RosterException::class);

        $this->generate(['', '  '], '2026-08-01', '2026-08-01');
    }

    #[Test]
    public function inactive_employees_are_skipped(): void
    {
        $inactive = Employee::factory()->inactive()->create();

        $this->rosters->generate(
            employeeIds: [$this->employee->id, $inactive->id],
            locationId: $this->location->id,
            startDate: Carbon::parse('2026-08-01'),
            endDate: Carbon::parse('2026-08-02'),
            pattern: ['S1'],
        );

        $this->assertDatabaseCount('shift_rosters', 2);
        $this->assertDatabaseMissing('shift_rosters', ['employee_id' => $inactive->id]);
    }

    #[Test]
    public function the_preview_shows_what_the_pattern_will_produce(): void
    {
        $preview = $this->rosters->preview(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
            ['S1', 'S2', 'S3', 'OFF'],
            limit: 5,
        );

        $this->assertCount(5, $preview);
        $this->assertSame(['S1', 'S2', 'S3', 'OFF', 'S1'], $preview->pluck('token')->all());
        $this->assertSame('2026-08-01', $preview->first()['date']);
    }
}
