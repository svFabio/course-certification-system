<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Session;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\Config;

class AttendanceService
{
    public function findSession(?int $sessionId, ?string $sessionCode): ?Session
    {
        if ($sessionId) {
            return Session::with('group')->find($sessionId);
        }

        if ($sessionCode && is_numeric($sessionCode)) {
            return Session::with('group')->find((int) $sessionCode);
        }

        if ($sessionCode && preg_match('/(\d+)/', $sessionCode, $matches)) {
            return Session::with('group')->find((int) $matches[1]);
        }

        return null;
    }

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

    public function registerFromQR(Session $session, int $preinscriptionId, float $lat, float $lng): Attendance
    {
        $labLat = Config::float('attendance.lab_lat');
        $labLng = Config::float('attendance.lab_lng');
        $maxRadius = Config::float('attendance.radius_meters');

        $distance = BusinessRules::haversineDistance($lat, $lng, $labLat, $labLng);
        $status = $distance <= $maxRadius
            ? AttendanceStatus::PRESENTE
            : AttendanceStatus::AUSENTE;

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

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return BusinessRules::haversineDistance($lat1, $lng1, $lat2, $lng2);
    }

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
