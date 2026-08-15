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
    */

    'enforce_geofence' => (bool) env('HRIS_ENFORCE_GEOFENCE', true),

    /*
    |--------------------------------------------------------------------------
    | Shift & attendance windows
    |--------------------------------------------------------------------------
    |
    | Spec §34 for the late threshold. The check-in window lets an employee
    | clock in before the shift officially starts; the check-out grace lets
    | them clock out after it ends (important for the cross-day shift 3).
    |
    */

    'default_late_tolerance_minutes' => (int) env('HRIS_DEFAULT_LATE_TOLERANCE_MINUTES', 15),

    'checkin_early_window_minutes' => (int) env('HRIS_CHECKIN_EARLY_WINDOW_MINUTES', 120),

    'checkout_grace_minutes' => (int) env('HRIS_CHECKOUT_GRACE_MINUTES', 180),

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
