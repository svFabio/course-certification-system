<?php

use App\Support\BusinessRules;

return [

    /*
    |--------------------------------------------------------------------------
    | Laboratory coordinates
    |--------------------------------------------------------------------------
    | Default: UMSS Cochabamba. Override via LAB_LAT / LAB_LNG in .env.
    */
    'lab_lat' => (float) env('LAB_LAT', BusinessRules::DEFAULT_LAB_LAT),
    'lab_lng' => (float) env('LAB_LNG', BusinessRules::DEFAULT_LAB_LNG),

    /*
    |--------------------------------------------------------------------------
    | Attendance radius (meters)
    |--------------------------------------------------------------------------
    | Students within this radius are marked "presente".
    | Outside radius → "para revisión" (ausente + flagged).
    */
    'radius_meters' => (float) env('ATTENDANCE_RADIUS_METERS', BusinessRules::ATTENDANCE_RADIUS_METERS),

];
