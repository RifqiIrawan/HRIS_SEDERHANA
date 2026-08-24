<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Geofence & GPS
    |--------------------------------------------------------------------------
    |
    | Spec §12, §22, §23. These are the fallbacks used when a location row does
    | not override them. The per-location columns radius_meter and
    | gps_accuracy_limit always win when present.
    |
    */

    'default_radius_meter' => (float) env('HRIS_DEFAULT_RADIUS_METER', 10),

    'default_gps_accuracy_limit' => (float) env('HRIS_DEFAULT_GPS_ACCURACY_LIMIT', 20),

    /*
    | Testing escape hatch. With this off, GeofenceService still computes and
    | records the distance and accuracy exactly as before, but stops rejecting
    | a reading for being too far away or too imprecise — which is what makes
    | the flow exercisable from a desktop browser, where the reported accuracy
    | is routinely hundreds of metres.
    |
    | It must be true in production: off, any device anywhere can clock in for
    | a shift. The check-in screen says so on the page while it is disabled.
    |
    | So the default follows APP_ENV rather than being a flat constant: only a
    | developer environment starts relaxed, and every other value of APP_ENV —
    | production, staging, or anything unrecognised — enforces. A .env carried
    | over from a laptop therefore cannot silently disarm the geofence on a
    | server; the environment name has to say "local" for that to happen.
    |
    | HRIS_ENFORCE_GEOFENCE still overrides in either direction when a specific
    | environment needs the opposite of its default.
    */

    'enforce_geofence' => (bool) env(
        'HRIS_ENFORCE_GEOFENCE',
        ! in_array(env('APP_ENV', 'production'), ['local', 'development'], true),
    ),

    /*
    |--------------------------------------------------------------------------
    | Shift & attendance windows
    |--------------------------------------------------------------------------
    |
    | Spec §34 for the late threshold. The rest bound the two actions on either
    | side, which is what stops a shift being opened or closed at a time that
    | cannot plausibly belong to it.
    |
    | Check-in is allowed from `checkin_early_window_minutes` before the shift
    | starts until `checkin_late_window_minutes` after it. Check-out is allowed
    | from `checkout_early_window_minutes` before the shift ends until
    | `checkout_grace_minutes` after it — the grace matters most for the
    | cross-day night shift, which ends the following morning.
    |
    | The check-in window still closes at the shift's own end when that comes
    | first, so a shift shorter than the late window cannot be opened after it
    | is already over.
    |
    */

    'default_late_tolerance_minutes' => (int) env('HRIS_DEFAULT_LATE_TOLERANCE_MINUTES', 15),

    'checkin_early_window_minutes' => (int) env('HRIS_CHECKIN_EARLY_WINDOW_MINUTES', 240),

    'checkin_late_window_minutes' => (int) env('HRIS_CHECKIN_LATE_WINDOW_MINUTES', 240),

    'checkout_early_window_minutes' => (int) env('HRIS_CHECKOUT_EARLY_WINDOW_MINUTES', 240),

    'checkout_grace_minutes' => (int) env('HRIS_CHECKOUT_GRACE_MINUTES', 420),

    /*
    |--------------------------------------------------------------------------
    | Attendance photo
    |--------------------------------------------------------------------------
    |
    | Spec §26. Photos are stored as files on the "attendance" disk; the
    | database only keeps path/name/mime/size.
    |
    */

    'photo' => [
        'disk' => env('HRIS_ATTENDANCE_PHOTO_DISK', 'attendance'),
        'max_kb' => (int) env('HRIS_ATTENDANCE_PHOTO_MAX_KB', 5120),
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverse geocoding
    |--------------------------------------------------------------------------
    |
    | Turns the GPS reading into a human-readable address for the check-in
    | screen and the stored attendance row. It is descriptive only — the
    | geofence verdict never consults it — so every setting here is allowed to
    | fail: disable the lookup, take it offline, or let it time out, and
    | attendance still works with a null address.
    |
    | Nominatim's usage policy requires an identifying User-Agent and tolerates
    | about one request per second, which is why results are cached.
    |
    */

    'geocoding' => [
        'enabled' => (bool) env('HRIS_GEOCODING_ENABLED', true),
        'endpoint' => env('HRIS_GEOCODING_ENDPOINT', 'https://nominatim.openstreetmap.org/reverse'),
        'timeout_seconds' => (int) env('HRIS_GEOCODING_TIMEOUT', 5),
        'language' => env('HRIS_GEOCODING_LANGUAGE', 'id'),
        'user_agent' => env('HRIS_GEOCODING_USER_AGENT', 'HRIS-JuruParkir/1.0'),
        'cache_ttl_minutes' => (int) env('HRIS_GEOCODING_CACHE_TTL_MINUTES', 1440),
        // Coordinates are rounded to this many decimals before becoming a
        // cache key: 4 decimals ≈ 11 m, so a stationary employee's repeated
        // readings all hit the same cached entry.
        'cache_precision' => (int) env('HRIS_GEOCODING_CACHE_PRECISION', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Earth radius used by the Haversine formula (metres)
    |--------------------------------------------------------------------------
    */

    'earth_radius_meters' => 6371000.0,

];
