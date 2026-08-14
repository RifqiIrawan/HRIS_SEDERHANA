<?php

namespace App\Services;

use App\Models\Location;

/**
 * Spec §23, §24, §52 — the single authority on whether a GPS reading is close
 * enough to a location to count.
 *
 * Nothing here reads anything the browser computed. The frontend sends latitude,
 * longitude and the device's own accuracy estimate; the distance is always
 * recalculated here (spec §24: "Nilai distance dari frontend tidak dipercaya").
 */
class GeofenceService
{
    public const OK = 'OK';

    public const LOCATION_INACTIVE = 'LOCATION_INACTIVE';

    public const ACCURACY_TOO_LOW = 'ACCURACY_TOO_LOW';

    public const OUT_OF_RADIUS = 'OUT_OF_RADIUS';

    /**
     * Great-circle distance between two coordinates, in metres.
     *
     * Uses the asin form of the Haversine formula, which stays numerically
     * stable at the very short distances this app deals in (single-digit
     * metres) where the atan2 form loses precision.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = (float) config('hris.earth_radius_meters');

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }

    /**
     * Decide whether a reading may be accepted for this location.
     *
     * Both thresholds come from the location row, so a site with a bigger
     * forecourt can be widened without touching code.
     *
     * @return array{
     *     valid: bool,
     *     code: string,
     *     message: string,
     *     distance: float,
     *     accuracy: float,
     *     radius_meter: float,
     *     gps_accuracy_limit: float
     * }
     */
    public function validate(float $userLat, float $userLng, float $accuracy, Location $location): array
    {
        $radius = $location->effectiveRadiusMeter();
        $accuracyLimit = $location->effectiveAccuracyLimit();

        // Round to centimetres so the verdict always agrees with the distance
        // the user is shown; comparing raw floats can reject a reading the UI
        // rendered as exactly "10.00 m".
        $distance = round($this->calculateDistance($userLat, $userLng, $location->latitude, $location->longitude), 2);
        $accuracy = round($accuracy, 2);

        $result = [
            'valid' => false,
            'code' => self::OK,
            'message' => '',
            'distance' => $distance,
            'accuracy' => $accuracy,
            'radius_meter' => $radius,
            'gps_accuracy_limit' => $accuracyLimit,
        ];

        if (! $location->isActive()) {
            return [...$result, 'code' => self::LOCATION_INACTIVE, 'message' => 'Lokasi absensi sedang tidak aktif.'];
        }

        // Spec §22 — a reading the device itself cannot vouch for is refused
        // before distance is even considered.
        if ($accuracy > $accuracyLimit) {
            return [...$result,
                'code' => self::ACCURACY_TOO_LOW,
                'message' => sprintf(
                    'GPS kurang akurat (%.1f m, batas %.0f m). Silakan coba kembali di area terbuka.',
                    $accuracy,
                    $accuracyLimit,
                ),
            ];
        }

        // Spec §23 — distance <= radius is valid, anything beyond is rejected.
        if ($distance > $radius) {
            return [...$result,
                'code' => self::OUT_OF_RADIUS,
                'message' => sprintf(
                    'Anda berada %.1f m dari titik lokasi (maksimal %.0f m). Mendekatlah ke titik absensi.',
                    $distance,
                    $radius,
                ),
            ];
        }

        return [...$result, 'valid' => true, 'message' => 'Lokasi valid.'];
    }
}
