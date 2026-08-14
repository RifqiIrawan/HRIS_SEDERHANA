<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Services\GeofenceService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Spec §23, §24, §59 — the geofence boundary table, verified exactly.
 *
 * These run without the database: a Location is built in memory so the maths
 * is tested on its own.
 */
class GeofenceServiceTest extends TestCase
{
    private GeofenceService $geofence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geofence = new GeofenceService;
    }

    private function location(int $radius = 10, int $accuracyLimit = 20, string $status = Location::ACTIVE): Location
    {
        return new Location([
            'location_code' => 'LOC001',
            'location_name' => 'Parkir Mall A',
            'latitude' => -6.1944000,
            'longitude' => 106.8229000,
            'radius_meter' => $radius,
            'gps_accuracy_limit' => $accuracyLimit,
            'status' => $status,
        ]);
    }

    /**
     * Moves north from the location by a given number of metres.
     * One degree of latitude is ~111,320 m, and moving along a meridian keeps
     * the conversion exact regardless of longitude.
     */
    private function pointMetresNorth(Location $location, float $metres): array
    {
        return [$location->latitude + ($metres / 111320.0), $location->longitude];
    }

    #[Test]
    public function it_measures_a_known_distance_accurately(): void
    {
        $location = $this->location();
        [$lat, $lng] = $this->pointMetresNorth($location, 100);

        $distance = $this->geofence->calculateDistance(
            $lat, $lng, $location->latitude, $location->longitude,
        );

        // Haversine on a 6 371 km sphere vs the 111 320 m/degree approximation
        // used to build the point: agreement to a few centimetres is expected.
        $this->assertEqualsWithDelta(100.0, $distance, 0.5);
    }

    #[Test]
    public function distance_is_zero_at_the_exact_point(): void
    {
        $location = $this->location();

        $this->assertSame(0.0, $this->geofence->calculateDistance(
            $location->latitude, $location->longitude,
            $location->latitude, $location->longitude,
        ));
    }

    /**
     * Spec §23 table: 2 m, 5 m, 9.9 m and 10 m are VALID; 10.1 m and 25 m are
     * REJECT.
     */
    #[Test]
    #[DataProvider('distanceCases')]
    public function it_applies_the_ten_metre_geofence(float $metres, bool $expectedValid): void
    {
        $location = $this->location();
        [$lat, $lng] = $this->pointMetresNorth($location, $metres);

        $result = $this->geofence->validate($lat, $lng, 5.0, $location);

        $this->assertSame(
            $expectedValid,
            $result['valid'],
            sprintf('Jarak %.2f m seharusnya %s', $metres, $expectedValid ? 'VALID' : 'REJECT'),
        );

        if (! $expectedValid) {
            $this->assertSame(GeofenceService::OUT_OF_RADIUS, $result['code']);
        }
    }

    public static function distanceCases(): array
    {
        return [
            '2 m → VALID' => [2.0, true],
            '5 m → VALID' => [5.0, true],
            '9.9 m → VALID' => [9.9, true],
            '9.99 m → VALID' => [9.99, true],
            '10 m → VALID' => [10.0, true],
            '10.1 m → REJECT' => [10.1, false],
            '25 m → REJECT' => [25.0, false],
        ];
    }

    /** Spec §22 / §59: 5 m and 20 m pass, 21 m is refused. */
    #[Test]
    #[DataProvider('accuracyCases')]
    public function it_applies_the_gps_accuracy_limit(float $accuracy, bool $expectedValid): void
    {
        $location = $this->location();
        [$lat, $lng] = $this->pointMetresNorth($location, 3);

        $result = $this->geofence->validate($lat, $lng, $accuracy, $location);

        $this->assertSame($expectedValid, $result['valid']);

        if (! $expectedValid) {
            $this->assertSame(GeofenceService::ACCURACY_TOO_LOW, $result['code']);
        }
    }

    public static function accuracyCases(): array
    {
        return [
            '5 m → PASS' => [5.0, true],
            '20 m → PASS' => [20.0, true],
            '20.01 m → REJECT' => [20.01, false],
            '21 m → REJECT' => [21.0, false],
        ];
    }

    #[Test]
    public function accuracy_is_checked_before_distance(): void
    {
        $location = $this->location();
        [$lat, $lng] = $this->pointMetresNorth($location, 500);

        $result = $this->geofence->validate($lat, $lng, 50.0, $location);

        // Both rules fail here; the accuracy message is the actionable one, so
        // it is the one the employee should see.
        $this->assertSame(GeofenceService::ACCURACY_TOO_LOW, $result['code']);
    }

    #[Test]
    public function an_inactive_location_never_validates(): void
    {
        $location = $this->location(status: Location::INACTIVE);

        $result = $this->geofence->validate(
            $location->latitude, $location->longitude, 1.0, $location,
        );

        $this->assertFalse($result['valid']);
        $this->assertSame(GeofenceService::LOCATION_INACTIVE, $result['code']);
    }

    #[Test]
    public function it_honours_per_location_thresholds(): void
    {
        // A site configured tighter than the default must be enforced tighter.
        $location = $this->location(radius: 5, accuracyLimit: 10);
        [$lat, $lng] = $this->pointMetresNorth($location, 8);

        $result = $this->geofence->validate($lat, $lng, 5.0, $location);

        $this->assertFalse($result['valid']);
        $this->assertSame(GeofenceService::OUT_OF_RADIUS, $result['code']);
        $this->assertSame(5.0, $result['radius_meter']);
        $this->assertSame(10.0, $result['gps_accuracy_limit']);
    }

    #[Test]
    public function it_reports_the_distance_it_measured(): void
    {
        $location = $this->location();
        [$lat, $lng] = $this->pointMetresNorth($location, 4.8);

        $result = $this->geofence->validate($lat, $lng, 7.2, $location);

        $this->assertTrue($result['valid']);
        $this->assertEqualsWithDelta(4.8, $result['distance'], 0.05);
        $this->assertSame(7.2, $result['accuracy']);
    }
}
