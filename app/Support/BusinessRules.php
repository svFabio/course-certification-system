<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CertificateType;

final class BusinessRules
{
    /*
    |--------------------------------------------------------------------------
    | Pricing (Bs.)
    |--------------------------------------------------------------------------
    */
    public const PRICING = [
        '20' => ['umss' => 80, 'externo' => 100, 'auxiliar' => 40],
        '30' => ['umss' => 120, 'externo' => 150, 'auxiliar' => 60],
    ];

    /*
    |--------------------------------------------------------------------------
    | Grade rules
    |--------------------------------------------------------------------------
    */
    public const MINIMUM_PASSING_GRADE = 70;

    public const GRADE_MIN = 0;

    public const GRADE_MAX = 100;

    /*
    |--------------------------------------------------------------------------
    | Attendance / Geolocation
    |--------------------------------------------------------------------------
    */
    public const DEFAULT_LAB_LAT = -17.7833;

    public const DEFAULT_LAB_LNG = -66.1500;

    public const ATTENDANCE_RADIUS_METERS = 100;

    /*
    |--------------------------------------------------------------------------
    | Group constraints
    |--------------------------------------------------------------------------
    */
    public const MIN_GROUP_CAPACITY = 15;

    /*
    |--------------------------------------------------------------------------
    | Evaluation
    |--------------------------------------------------------------------------
    */
    public const EVALUATION_TOTAL_PERCENT = 100;

    /*
    |--------------------------------------------------------------------------
    | Course hours
    |--------------------------------------------------------------------------
    */
    public const VALID_HOURS = ['20', '30'];

    /*
    |--------------------------------------------------------------------------
    | Session duration by course hours
    |--------------------------------------------------------------------------
    */
    public const SESSION_DURATION_HOURS = [
        '20' => 2.0,
        '30' => 3.0,
    ];

    public const SESSIONS_PER_COURSE = 10;

    /*
    |--------------------------------------------------------------------------
    | UMSS Official Class Schedules (Academic Periods)
    |--------------------------------------------------------------------------
    */
    public const UMSS_SCHEDULE_BLOCKS = [
        '06:45' => '06:45 - 08:15 (1er Periodo)',
        '08:15' => '08:15 - 09:45 (2do Periodo)',
        '09:45' => '09:45 - 11:15 (3er Periodo)',
        '11:15' => '11:15 - 12:45 (4to Periodo)',
        '12:45' => '12:45 - 14:15 (Mediodía)',
        '14:15' => '14:15 - 15:45 (5to Periodo)',
        '15:45' => '15:45 - 17:15 (6to Periodo)',
        '17:15' => '17:15 - 18:45 (7mo Periodo)',
        '18:45' => '18:45 - 20:15 (8vo Periodo)',
        '20:15' => '20:15 - 21:45 (9no Periodo)',
    ];

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public static function calculatePrice(int $hours, string $participantType): float
    {
        $hoursKey = (string) $hours;

        if (! isset(self::PRICING[$hoursKey])) {
            throw new \InvalidArgumentException("Invalid hours: {$hours}. Must be 20 or 30.");
        }

        $price = self::PRICING[$hoursKey][$participantType] ?? null;

        if ($price === null) {
            throw new \InvalidArgumentException("Invalid participant type: {$participantType}");
        }

        return (float) $price;
    }

    public static function determineCertificateType(float $grade): CertificateType
    {
        return $grade >= self::MINIMUM_PASSING_GRADE ? CertificateType::APROBACION : CertificateType::ASISTENCIA;
    }

    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFrom) * cos($latTo)
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get pricing for a given course hours and participant type.
     * Returns the full pricing array if no participant type given.
     */
    public static function getPricing(?string $participantType = null): float|array
    {
        if ($participantType === null) {
            return self::PRICING;
        }

        // Return pricing for all hour types for this participant
        $result = [];
        foreach (self::PRICING as $hours => $prices) {
            $result[$hours] = $prices[$participantType] ?? 0;
        }

        return $result;
    }
}
