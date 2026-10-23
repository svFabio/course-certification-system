<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CertificateType;
use App\Enums\TipoParticipante;
use Carbon\Carbon;
use Closure;

final class BusinessRules
{
    /*
    |--------------------------------------------------------------------------
    | Pricing (Bs.)
    |--------------------------------------------------------------------------
    */
    /**
     * Default pre-calculation only (Bs.): prefills course price columns on
     * creation or when carga_horaria changes. NOT a source of truth for reads —
     * charge and display flows must read the course columns precio_umss,
     * precio_externo and precio_auxiliar (AGENTS.md invariant 1).
     */
    public const PRICING = [
        '20' => ['umss' => 80, 'externo' => 100, 'auxiliar' => 40],
        '30' => ['umss' => 120, 'externo' => 150, 'auxiliar' => 60],
    ];

    /*
    |--------------------------------------------------------------------------
    | Financial Distribution (HU-19)
    |--------------------------------------------------------------------------
    */
    public const FACULTY_RETENTION_PERCENT = 20.0;

    public const LAB_AND_TEACHER_PERCENT = 80.0;

    /**
     * Compute institutional distribution of net course revenue (HU-19).
     *
     * @return array{facultad: float, docente_lab: float}
     */
    public static function calculateFinancialDistribution(float $netAmount): array
    {
        return [
            'facultad' => round($netAmount * (self::FACULTY_RETENTION_PERCENT / 100), 2),
            'docente_lab' => round($netAmount * (self::LAB_AND_TEACHER_PERCENT / 100), 2),
        ];
    }

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

    public const DEFAULT_ATTENDANCE_WEIGHT = 50;

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
        '20' => 1.5,
        '30' => 2.5,
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

    public static function calculateSessionEndTime(Carbon|string $horaInicio, string|int $cargaHoraria): Carbon
    {
        $inicio = $horaInicio instanceof Carbon ? $horaInicio->copy() : Carbon::parse($horaInicio);
        $durationHours = self::SESSION_DURATION_HOURS[(string) $cargaHoraria] ?? 1.5;
        $minutes = (int) round($durationHours * 60);

        return $inicio->addMinutes($minutes);
    }

    public static function validateMaxGroupCapacity(int|string|null $cupoMinimo): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($cupoMinimo): void {
            $min = (int) $cupoMinimo;
            if ($min > 0 && (int) $value < $min) {
                $fail("El cupo máximo debe ser mayor o igual al cupo mínimo ({$min}).");
            }
        };
    }

    public static function calculatePrice(int $hours, string|TipoParticipante $participantType): float
    {
        $type = $participantType instanceof TipoParticipante
            ? $participantType
            : TipoParticipante::tryFrom($participantType);

        if ($type === null) {
            throw new \InvalidArgumentException("Invalid participant type: {$participantType}");
        }

        $hoursKey = (string) $hours;

        if (! isset(self::PRICING[$hoursKey])) {
            throw new \InvalidArgumentException("Invalid hours: {$hours}. Must be 20 or 30.");
        }

        $price = self::PRICING[$hoursKey][$type->value] ?? null;

        if ($price === null) {
            throw new \InvalidArgumentException("Invalid participant type: {$type->value}");
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
    public static function getPricing(string|TipoParticipante|null $participantType = null): float|array
    {
        if ($participantType === null) {
            return self::PRICING;
        }

        $type = $participantType instanceof TipoParticipante
            ? $participantType
            : TipoParticipante::tryFrom($participantType);

        if ($type === null) {
            throw new \InvalidArgumentException("Invalid participant type: {$participantType}");
        }

        // Return pricing for all hour types for this participant
        $result = [];
        foreach (self::PRICING as $hours => $prices) {
            if (! isset($prices[$type->value])) {
                throw new \InvalidArgumentException("Missing price configuration for participant type: {$type->value}");
            }
            $result[$hours] = (float) $prices[$type->value];
        }

        return $result;
    }
}
