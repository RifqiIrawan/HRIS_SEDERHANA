<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Spec §57 — attendance acceptance criteria AC-001 … AC-015.
 */
class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private User $user;

    private Location $location;

    private Shift $shift;

    private ShiftRoster $roster;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('parkops.photo.disk'));

        // A Tuesday morning, ten minutes into the 06:00 shift.
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 6, 10));

        $this->employee = Employee::factory()->create();
        $this->user = User::factory()->forEmployee($this->employee)->create();
        $this->location = Location::factory()->create();
        $this->shift = Shift::factory()->create();

        $this->roster = ShiftRoster::factory()
            ->forShift($this->shift, Carbon::today())
            ->create([
                'employee_id' => $this->employee->id,
                'location_id' => $this->location->id,
            ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Moves north from the location by a given number of metres. */
    private function pointMetresNorth(float $metres): array
    {
        return [
            'latitude' => $this->location->latitude + ($metres / 111320.0),
            'longitude' => $this->location->longitude,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(float $metres = 4.0, float $accuracy = 7.2, array $overrides = []): array
    {
        return array_merge($this->pointMetresNorth($metres), [
            'accuracy' => $accuracy,
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ], $overrides);
    }

    /* ── AC-001 / AC-002 ─────────────────────────────────────────────── */

    #[Test]
    public function ac001_check_in_requires_authentication(): void
    {
        $this->postJson(route('attendance.check-in'), $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function ac002_a_user_not_linked_to_an_employee_cannot_check_in(): void
    {
        $orphan = User::factory()->hr()->create();

        $this->actingAs($orphan)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_LINKED_TO_EMPLOYEE');
    }

    #[Test]
    public function ac002_the_employee_comes_from_the_session_not_the_request(): void
    {
        $someoneElse = Employee::factory()->create();

        // An injected employee_id must be ignored entirely.
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(overrides: [
                'employee_id' => $someoneElse->id,
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id]);
        $this->assertDatabaseMissing('attendances', ['employee_id' => $someoneElse->id]);
    }

    /* ── AC-003 ──────────────────────────────────────────────────────── */

    #[Test]
    public function ac003_check_in_requires_an_active_roster(): void
    {
        $this->roster->delete();

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_ACTIVE_ROSTER');
    }

    #[Test]
    public function ac003_an_off_day_offers_no_roster_to_check_in_against(): void
    {
        $this->roster->update([
            'shift_id' => null,
            'start_datetime' => null,
            'end_datetime' => null,
            'status' => ShiftRoster::OFF,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_ACTIVE_ROSTER');
    }

    /* ── AC-004 / AC-005 ─────────────────────────────────────────────── */

    #[Test]
    public function ac004_gps_coordinates_are_mandatory(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), [
                'accuracy' => 7.2,
                'photo' => UploadedFile::fake()->image('selfie.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    #[Test]
    public function ac005_accuracy_worse_than_twenty_metres_is_refused(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(accuracy: 21.0))
            ->assertStatus(422)
            ->assertJsonPath('code', 'ACCURACY_TOO_LOW');

        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function ac005_accuracy_of_exactly_twenty_metres_is_accepted(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(accuracy: 20.0))
            ->assertCreated();
    }

    /* ── AC-006 / AC-007 / AC-008 ────────────────────────────────────── */

    #[Test]
    public function ac006_the_distance_sent_by_the_client_is_ignored(): void
    {
        // Claim to be standing on the pin while actually 80 m away.
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(metres: 80, overrides: [
                'distance' => 0,
                'validation_status' => 'VALID',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'OUT_OF_RADIUS');

        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function ac007_within_ten_metres_is_accepted(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(metres: 9.5))
            ->assertCreated();

        $attendance = Attendance::first();

        // AC-014 — the backend's own measurement is what gets stored.
        $this->assertEqualsWithDelta(9.5, (float) $attendance->check_in_distance, 0.1);
    }

    #[Test]
    public function ac008_beyond_ten_metres_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(metres: 10.5))
            ->assertStatus(422)
            ->assertJsonPath('code', 'OUT_OF_RADIUS');
    }

    /* ── Relaxed geofence (testing switch) ───────────────────────────── */

    #[Test]
    public function with_enforcement_off_a_distant_reading_is_accepted_but_still_measured(): void
    {
        config()->set('parkops.enforce_geofence', false);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(metres: 4000, accuracy: 900))
            ->assertCreated();

        $attendance = Attendance::first();

        // The point of recording them anyway: the row still shows the check-in
        // came from 4 km away, so relaxed data is identifiable afterwards.
        $this->assertEqualsWithDelta(4000, (float) $attendance->check_in_distance, 5);
        $this->assertEqualsWithDelta(900, (float) $attendance->check_in_accuracy, 0.1);
    }

    #[Test]
    public function enforcement_off_does_not_revive_an_inactive_location(): void
    {
        config()->set('parkops.enforce_geofence', false);
        $this->location->update(['status' => Location::INACTIVE]);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'LOCATION_INACTIVE');
    }

    #[Test]
    public function the_check_in_screen_is_told_whether_the_geofence_is_enforced(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('attendance.index'))
            ->assertOk()
            ->assertJsonPath('data.enforce_geofence', true);

        config()->set('parkops.enforce_geofence', false);

        $this->actingAs($this->user)
            ->getJson(route('attendance.index'))
            ->assertOk()
            ->assertJsonPath('data.enforce_geofence', false);
    }

    /* ── AC-009 / AC-010 ─────────────────────────────────────────────── */

    #[Test]
    public function ac009_a_photo_is_mandatory(): void
    {
        $payload = $this->payload();
        unset($payload['photo']);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    #[Test]
    public function ac010_a_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(overrides: [
                'photo' => UploadedFile::fake()->create('payload.php', 20, 'application/x-php'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    #[Test]
    public function ac010_a_photo_over_five_megabytes_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(overrides: [
                'photo' => UploadedFile::fake()->create('huge.jpg', 6 * 1024, 'image/jpeg'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    /* ── AC-011 ──────────────────────────────────────────────────────── */

    #[Test]
    public function ac011_the_timestamp_is_the_servers(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(overrides: [
                // A manipulated browser clock must have no effect.
                'check_in_at' => '2020-01-01 00:00:00',
                'client_time' => '2020-01-01 00:00:00',
            ]))
            ->assertCreated();

        $this->assertSame(
            Carbon::now()->toDateTimeString(),
            Attendance::first()->check_in_at->toDateTimeString(),
        );
    }

    /* ── AC-012 ──────────────────────────────────────────────────────── */

    #[Test]
    public function ac012_a_second_check_in_for_the_same_shift_is_refused(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated();

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'OPEN_ATTENDANCE_EXISTS');

        $this->assertDatabaseCount('attendances', 1);
    }

    #[Test]
    public function ac012_checking_in_again_after_checking_out_is_refused(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 14, 1));
        $this->actingAs($this->user)->postJson(route('attendance.check-out'), $this->payload())
            ->assertOk();

        // Back inside the same shift's check-in window — which now closes four
        // hours after the 06:00 start — with the day already complete.
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 9, 0));
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'DUPLICATE_CHECK_IN');

        $this->assertDatabaseCount('attendances', 1);
    }

    /* ── Check-in / check-out windows ────────────────────────────────── */

    /** Shift runs 06:00 → 14:00, so both bounds are read off those two times. */
    private function attemptCheckIn(int $hour, int $minute)
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, $hour, $minute));

        return $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());
    }

    private function checkOutAt(Carbon $when)
    {
        Carbon::setTestNow($when);

        return $this->actingAs($this->user)->postJson(route('attendance.check-out'), $this->payload());
    }

    #[Test]
    public function check_in_opens_four_hours_before_the_shift_starts(): void
    {
        $this->attemptCheckIn(2, 0)->assertCreated();
    }

    #[Test]
    public function check_in_is_refused_more_than_four_hours_early(): void
    {
        $this->attemptCheckIn(1, 59)
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_ACTIVE_ROSTER');

        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function check_in_stays_open_until_four_hours_after_the_shift_starts(): void
    {
        $this->attemptCheckIn(10, 0)->assertCreated();
    }

    #[Test]
    public function check_in_is_refused_more_than_four_hours_late(): void
    {
        $this->attemptCheckIn(10, 1)
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_ACTIVE_ROSTER');

        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * The late window is generous enough to outlive a short shift, so the
     * shift's own end has to keep bounding it — otherwise a two-hour shift
     * could be opened an hour after it finished.
     */
    #[Test]
    public function a_shift_shorter_than_the_late_window_still_closes_at_its_own_end(): void
    {
        $this->roster->update(['end_datetime' => Carbon::create(2026, 8, 11, 8, 0)]);

        $this->attemptCheckIn(8, 1)
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_ACTIVE_ROSTER');
    }

    #[Test]
    public function check_out_is_refused_more_than_four_hours_before_the_shift_ends(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated();

        // Shift ends 14:00, so the earliest check-out is 10:00.
        $this->checkOutAt(Carbon::create(2026, 8, 11, 9, 59))
            ->assertStatus(422)
            ->assertJsonPath('code', 'CHECK_OUT_TOO_EARLY');

        $this->assertNull(Attendance::first()->check_out_at);
    }

    #[Test]
    public function check_out_opens_four_hours_before_the_shift_ends(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        $this->checkOutAt(Carbon::create(2026, 8, 11, 10, 0))->assertOk();

        $this->assertNotNull(Attendance::first()->check_out_at);
    }

    #[Test]
    public function check_out_stays_open_until_seven_hours_after_the_shift_ends(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        $this->checkOutAt(Carbon::create(2026, 8, 11, 21, 0))->assertOk();

        $this->assertNotNull(Attendance::first()->check_out_at);
    }

    #[Test]
    public function check_out_is_refused_more_than_seven_hours_after_the_shift_ends(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        $this->checkOutAt(Carbon::create(2026, 8, 11, 21, 1))
            ->assertStatus(422)
            ->assertJsonPath('code', 'CHECK_OUT_WINDOW_EXPIRED');

        $this->assertNull(Attendance::first()->check_out_at);
    }

    /**
     * The floor may only ever delay a check-out, never invalidate one that has
     * already happened — a late check-in must still be closeable straight away.
     */
    #[Test]
    public function a_check_in_after_the_check_out_floor_can_close_immediately(): void
    {
        // 10:00 is both the last minute to check in and the first minute the
        // check-out window is open.
        $this->attemptCheckIn(10, 0)->assertCreated();

        $this->checkOutAt(Carbon::create(2026, 8, 11, 10, 1))->assertOk();
    }

    #[Test]
    public function the_screen_is_told_the_four_bounds_the_server_enforces(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('attendance.index'))
            ->assertOk()
            ->assertJsonPath('data.roster.checkin_opens', '02:00')
            ->assertJsonPath('data.roster.checkin_closes', '10:00')
            ->assertJsonPath('data.roster.checkout_opens', '10:00')
            ->assertJsonPath('data.roster.checkout_closes', '21:00');
    }

    /**
     * The night shift is the reason these labels carry a date at all: half its
     * window falls on the following morning, where a bare "02:00" would read as
     * the wrong day entirely.
     */
    #[Test]
    public function a_cross_day_shift_carries_the_date_on_bounds_that_land_the_next_morning(): void
    {
        $night = Shift::factory()->night()->create(['shift_code' => 'N1']);

        $this->roster->update([
            'shift_id' => $night->id,
            'start_datetime' => Carbon::create(2026, 8, 11, 22, 0),
            'end_datetime' => Carbon::create(2026, 8, 12, 6, 0),
        ]);

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 22, 5));

        $this->actingAs($this->user)
            ->getJson(route('attendance.index'))
            ->assertOk()
            ->assertJsonPath('data.roster.checkin_opens', '18:00')
            ->assertJsonPath('data.roster.checkin_closes', '12 Aug 02:00')
            ->assertJsonPath('data.roster.checkout_opens', '12 Aug 02:00')
            ->assertJsonPath('data.roster.checkout_closes', '12 Aug 13:00');
    }

    /* ── AC-013 / AC-014 / AC-015 ────────────────────────────────────── */

    #[Test]
    public function ac013_to_ac015_gps_distance_and_photo_are_all_persisted(): void
    {
        $point = $this->pointMetresNorth(4.0);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload(metres: 4.0, accuracy: 7.2))
            ->assertCreated();

        $attendance = Attendance::with('photos')->first();

        // AC-013 — the raw reading.
        $this->assertEqualsWithDelta($point['latitude'], (float) $attendance->check_in_latitude, 0.0000001);
        $this->assertEqualsWithDelta($point['longitude'], (float) $attendance->check_in_longitude, 0.0000001);
        $this->assertSame(7.2, (float) $attendance->check_in_accuracy);

        // AC-014 — the distance the backend computed.
        $this->assertEqualsWithDelta(4.0, (float) $attendance->check_in_distance, 0.1);

        // AC-015 — the file on disk plus its metadata row (spec §26).
        $photo = $attendance->photos->firstWhere('photo_type', AttendancePhoto::CHECK_IN);

        $this->assertNotNull($photo);
        $this->assertSame($photo->file_path, $attendance->check_in_photo);
        Storage::disk(config('parkops.photo.disk'))->assertExists($photo->file_path);
        $this->assertGreaterThan(0, $photo->file_size);
        $this->assertStringStartsWith('image/', $photo->mime_type);
    }

    /* ── Lateness (spec §34) ─────────────────────────────────────────── */

    #[Test]
    public function arriving_inside_the_tolerance_is_on_time(): void
    {
        // 06:15 with a 15-minute tolerance is still ON TIME.
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 6, 15));

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated();

        $this->assertSame(0, Attendance::first()->late_minutes);
    }

    #[Test]
    public function arriving_past_the_tolerance_is_late_measured_from_the_shift_start(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 6, 20));

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated();

        // Reported against 06:00, not against the 06:15 threshold.
        $this->assertSame(20, Attendance::first()->late_minutes);
    }

    #[Test]
    public function status_becomes_late_only_once_the_day_is_closed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 6, 30));
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        // Spec §31 — an open day is INCOMPLETE until check-out.
        $this->assertSame(Attendance::INCOMPLETE, Attendance::first()->status);

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 14, 2));
        $this->actingAs($this->user)->postJson(route('attendance.check-out'), $this->payload())
            ->assertOk();

        $this->assertSame(Attendance::LATE, Attendance::first()->status);
    }

    /* ── Check-out (spec §28) ────────────────────────────────────────── */

    #[Test]
    public function check_out_without_a_check_in_is_refused(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-out'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_OPEN_ATTENDANCE');
    }

    #[Test]
    public function check_out_is_geofenced_too(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 14, 0));

        // Being inside the radius at check-in does not license leaving from afar.
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-out'), $this->payload(metres: 60))
            ->assertStatus(422)
            ->assertJsonPath('code', 'OUT_OF_RADIUS');

        $this->assertNull(Attendance::first()->check_out_at);
    }

    #[Test]
    public function a_completed_day_is_marked_present_and_keeps_both_photos(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 14, 3));
        $this->actingAs($this->user)->postJson(route('attendance.check-out'), $this->payload())
            ->assertOk();

        $attendance = Attendance::with('photos')->first();

        $this->assertSame(Attendance::PRESENT, $attendance->status);
        $this->assertSame('2026-08-11 14:03:00', $attendance->check_out_at->toDateTimeString());
        $this->assertCount(2, $attendance->photos);
    }

    #[Test]
    public function check_out_long_after_the_shift_ended_is_refused(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        // Three days later — well past the grace window.
        Carbon::setTestNow(Carbon::create(2026, 8, 14, 9, 0));

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-out'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'CHECK_OUT_WINDOW_EXPIRED');
    }

    /* ── Cross-day shift (spec §17) ──────────────────────────────────── */

    #[Test]
    public function a_night_shift_that_ends_next_morning_resolves_to_the_day_it_started(): void
    {
        $this->roster->delete();

        $night = Shift::factory()->night()->create();
        $rosterDate = Carbon::create(2026, 8, 11);

        ShiftRoster::factory()->forShift($night, $rosterDate)->create([
            'employee_id' => $this->employee->id,
            'location_id' => $this->location->id,
        ]);

        // Clock in at 22:05 on the 11th.
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 22, 5));
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated();

        // Clock out at 06:02 on the 12th — still the 11th's shift.
        Carbon::setTestNow(Carbon::create(2026, 8, 12, 6, 2));
        $this->actingAs($this->user)
            ->postJson(route('attendance.check-out'), $this->payload())
            ->assertOk();

        $attendance = Attendance::first();

        $this->assertSame('2026-08-11', $attendance->attendance_date->toDateString());
        $this->assertSame(Attendance::PRESENT, $attendance->status);
        $this->assertSame(0, $attendance->late_minutes);
    }

    /* ── Employee status ─────────────────────────────────────────────── */

    #[Test]
    public function an_inactive_employee_cannot_check_in(): void
    {
        $this->employee->update(['status' => Employee::INACTIVE]);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'EMPLOYEE_INACTIVE');
    }

    #[Test]
    public function an_inactive_location_blocks_check_in(): void
    {
        $this->location->update(['status' => Location::INACTIVE]);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'LOCATION_INACTIVE');
    }

    /* ── Reverse-geocoded address ────────────────────────────────────── */

    /**
     * Puts a canned Nominatim response behind the geocoder and switches the
     * lookup on, which phpunit.xml disables for every other test.
     *
     * @param  array<string, mixed>|null  $address  null makes the request fail
     */
    private function fakeGeocoder(?array $address): void
    {
        config()->set('parkops.geocoding.enabled', true);

        Http::fake([
            '*nominatim*' => $address === null
                ? Http::response(status: 503)
                : Http::response(['address' => $address, 'display_name' => 'abaikan']),
        ]);
    }

    #[Test]
    public function check_in_stores_the_reverse_geocoded_address(): void
    {
        $this->fakeGeocoder([
            'road' => 'Jalan M.H. Thamrin',
            // Both keys belong to the same group, so only one may be used.
            'village' => 'Gondangdia',
            'suburb' => 'Gondangdia',
            'city' => 'Jakarta Pusat',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.check_in_address', 'Jalan M.H. Thamrin, Gondangdia, Jakarta Pusat');

        $this->assertSame(
            'Jalan M.H. Thamrin, Gondangdia, Jakarta Pusat',
            Attendance::first()->check_in_address,
        );
    }

    #[Test]
    public function check_out_stores_its_own_address(): void
    {
        $this->fakeGeocoder(['road' => 'Jalan Kebon Sirih', 'city' => 'Jakarta Pusat']);

        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        Carbon::setTestNow(Carbon::create(2026, 8, 11, 14, 0));

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-out'), $this->payload())
            ->assertOk()
            ->assertJsonPath('data.check_out_address', 'Jalan Kebon Sirih, Jakarta Pusat');
    }

    #[Test]
    public function a_failing_geocoder_does_not_block_the_check_in(): void
    {
        $this->fakeGeocoder(null);

        $this->actingAs($this->user)
            ->postJson(route('attendance.check-in'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.check_in_address', null);

        // The attendance itself is unaffected: only the label is missing.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'check_in_address' => null,
            'status' => Attendance::INCOMPLETE,
        ]);
    }

    #[Test]
    public function the_geocode_endpoint_serves_the_check_in_screen(): void
    {
        $this->fakeGeocoder(['road' => 'Jalan Sudirman', 'city' => 'Jakarta Selatan']);

        $this->actingAs($this->user)
            ->getJson(route('attendance.geocode', [
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude,
            ]))
            ->assertOk()
            ->assertJsonPath('data.address', 'Jalan Sudirman, Jakarta Selatan');
    }

    #[Test]
    public function the_geocode_endpoint_rejects_coordinates_it_cannot_use(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('attendance.geocode', ['latitude' => 200, 'longitude' => 'utara']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /* ── Photo access control ────────────────────────────────────────── */

    #[Test]
    public function an_employee_cannot_view_another_employees_attendance_photo(): void
    {
        $this->actingAs($this->user)->postJson(route('attendance.check-in'), $this->payload());

        $photo = AttendancePhoto::first();
        $intruder = User::factory()->forEmployee(Employee::factory()->create())->create();

        $this->actingAs($intruder)->get(route('attendance.photo', $photo))->assertForbidden();
        $this->actingAs($this->user)->get(route('attendance.photo', $photo))->assertOk();
        $this->actingAs(User::factory()->hr()->create())->get(route('attendance.photo', $photo))->assertOk();
    }
}
