<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Preinscription;
use App\Models\Session;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\Config;

class AttendanceService
{
    public function findSession(?int $sessionId, ?string $sessionCode = null): ?Session
    {
        $resolvedId = $sessionId
            ?? (is_numeric($sessionCode) ? (int) $sessionCode : null)
            ?? (preg_match('/(\d+)/', (string) $sessionCode, $m) ? (int) $m[1] : null);

        return $resolvedId !== null
            ? Session::with('group')->find($resolvedId)
            : null;
    }

    public function findPreinscription(array $params, int $groupId): ?Preinscription
    {
        if (! empty($params['preinscription_id'])) {
            return Preinscription::where('group_id', $groupId)
                ->where('id', $params['preinscription_id'])
                ->first();
        }

        if (! empty($params['ci'])) {
            return Preinscription::where('group_id', $groupId)
                ->where('ci', $params['ci'])
                ->first();
        }

        if (! empty($params['email'])) {
            return Preinscription::where('group_id', $groupId)
                ->where('email', $params['email'])
                ->first();
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
                'lat' => null,
                'lng' => null,
                'distancia_metros' => null,
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
