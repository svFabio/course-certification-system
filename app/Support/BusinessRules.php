<?php

declare(strict_types=1);

namespace App\Support;

class BusinessRules
{
    public const PRICING = [
        '20' => ['umss' => 80, 'externo' => 100, 'auxiliar' => 40],
        '30' => ['umss' => 120, 'externo' => 150, 'auxiliar' => 60],
    ];

    public const MINIMUM_PASSING_GRADE = 70;

    public const MAX_ATTENDANCE_DISTANCE_METERS = 100;

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

    public static function determineCertificateType(float $grade): string
    {
        return $grade >= self::MINIMUM_PASSING_GRADE ? 'aprobacion' : 'asistencia';
    }

    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

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
}
