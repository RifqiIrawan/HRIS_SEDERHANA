<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reverse geocoding for the GPS readings taken at check-in and check-out.
 *
 * The result is a label for humans — "Jl. M.H. Thamrin, Menteng, Jakarta
 * Pusat" — and nothing else reads it. GeofenceService still decides validity
 * from the coordinates alone, which is what allows every failure here (the
 * provider being down, rate-limiting, no internet on the site) to degrade to a
 * null address instead of blocking an attendance.
 */
class GeocodingService
{
    /**
     * Address components in the order they read naturally in Indonesian, from
     * the street outwards. Each group contributes at most one part, so a
     * response carrying both `village` and `suburb` does not repeat itself.
     *
     * @var array<int, array<int, string>>
     */
    private const ADDRESS_PARTS = [
        ['road', 'pedestrian', 'footway', 'residential'],
        ['neighbourhood', 'hamlet', 'village', 'suburb'],
        ['city_district', 'municipality', 'town'],
        ['city', 'county', 'regency'],
        ['state'],
    ];

    /** Fits the varchar(255) columns with room for a multi-byte tail. */
    private const MAX_LENGTH = 240;

    /**
     * The address at these coordinates, or null when it cannot be determined.
     *
     * Never throws: a caller in the middle of writing an attendance row must
     * not have that write undone because a third-party HTTP call failed.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        $config = config('hris.geocoding');

        if (! $config['enabled']) {
            return null;
        }

        $precision = (int) $config['cache_precision'];
        $key = sprintf(
            'geocode:%s,%s',
            number_format($latitude, $precision, '.', ''),
            number_format($longitude, $precision, '.', ''),
        );

        // Only a successful lookup is cached. Caching the null would mean one
        // dropped connection blanks the address for everyone at that spot
        // until the TTL expires.
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $address = $this->lookup($latitude, $longitude, $config);

        if ($address !== null) {
            Cache::put($key, $address, now()->addMinutes((int) $config['cache_ttl_minutes']));
        }

        return $address;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function lookup(float $latitude, float $longitude, array $config): ?string
    {
        try {
            $response = Http::withHeaders([
                // Nominatim rejects anonymous clients; see its usage policy.
                'User-Agent' => (string) $config['user_agent'],
                'Accept' => 'application/json',
            ])
                ->timeout((int) $config['timeout_seconds'])
                ->get((string) $config['endpoint'], [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    // Street level. Zooming further in mostly returns house
                    // numbers, which a parking attendant's position rarely has.
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => (string) $config['language'],
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $this->formatAddress($response->json() ?? []);
        } catch (Throwable $e) {
            // Debug, not error: an unreachable geocoder is an expected
            // condition on a site with poor connectivity, not a fault.
            Log::debug('Reverse geocoding gagal.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Builds the short label from the structured parts, falling back to the
     * provider's own `display_name` when the response has no breakdown.
     *
     * @param  array<string, mixed>  $payload
     */
    private function formatAddress(array $payload): ?string
    {
        $address = $payload['address'] ?? null;

        if (! is_array($address)) {
            return $this->truncate($payload['display_name'] ?? null);
        }

        $parts = [];

        foreach (self::ADDRESS_PARTS as $group) {
            foreach ($group as $key) {
                $value = trim((string) ($address[$key] ?? ''));

                if ($value === '' || in_array($value, $parts, true)) {
                    continue;
                }

                $parts[] = $value;

                break;
            }
        }

        if ($parts === []) {
            return $this->truncate($payload['display_name'] ?? null);
        }

        return $this->truncate(implode(', ', $parts));
    }

    private function truncate(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, self::MAX_LENGTH, '');
    }
}