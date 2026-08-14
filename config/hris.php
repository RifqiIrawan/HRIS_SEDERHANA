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
    | Earth radius used by the Haversine formula (metres)
    |--------------------------------------------------------------------------
    */

    'earth_radius_meters' => 6371000.0,

];
