<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Session;
use App\Support\BusinessRules;

class AttendanceService
{
    public function __construct() {}

    // TODO: Implement registerManual — instructor marks attendance manually
    public function registerManual(Session $session, int $preinscriptionId, string $status): Attendance
    {
        return Attendance::updateOrCreate(
            [
                'session_id' => $session->id,
                'preinscription_id' => $preinscriptionId,
            ],
            [
                'status' => AttendanceStatus::from($status),
            ]
        );
    }

    // Student scans QR, validate geo location
    public function registerFromQR(Session $session, int $preinscriptionId, float $lat, float $lng): Attendance
    {
        $labLat = (float) env('LAB_LAT', -17.7833);
        $labLng = (float) env('LAB_LNG', -66.1500);
        $maxRadius = (float) env('ATTENDANCE_RADIUS_METERS', BusinessRules::MAX_ATTENDANCE_DISTANCE_METERS);

        $distance = $this->calculateDistance($lat, $lng, $labLat, $labLng);
        $status = $distance <= $maxRadius ? AttendanceStatus::PRESENTE : AttendanceStatus::AUSENTE;

        return Attendance::updateOrCreate(
            [
                'session_id' => $session->id,
                'preinscription_id' => $preinscriptionId,
            ],
            [
                'status' => $status,
                'lat' => $lat,
                'lng' => $lng,
                'distancia_metros' => round($distance, 2),
            ]
        );
    }

    // Calculate distance using BusinessRules::haversineDistance
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return BusinessRules::haversineDistance($lat1, $lng1, $lat2, $lng2);
    }

    // Return attendance stats for a session
    public function getAttendanceStats(Session $session): array
    {
        $total = $session->attendances()->count();
        $presentes = $session->attendances()->where('status', AttendanceStatus::PRESENTE)->count();
        $ausentes = $session->attendances()->where('status', AttendanceStatus::AUSENTE)->count();
        $justificados = $session->attendances()->where('status', AttendanceStatus::JUSTIFICADO)->count();

        return [
            'total' => $total,
            'presentes' => $presentes,
            'ausentes' => $ausentes,
            'justificados' => $justificados,
        ];
    }
}
