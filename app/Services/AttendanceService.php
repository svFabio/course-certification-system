<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Support\BusinessRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

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

    /**
     * Only the course instructor that owns the session's group, or an admin,
     * may register attendance for it. Mirrors the ownership scoping used by
     * instructor Filament resources (whereHas('course.instructor_id', ...)).
     */
    public function canManageSession(Session $session, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        return Session::query()
            ->whereKey($session->id)
            ->whereHas('group.course', fn (Builder $query) => $query->where('instructor_id', $user->id))
            ->exists();
    }

    /**
     * Lookups are always scoped to the target group so a participant from
     * another group can never be resolved for this session.
     */
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
        $attendance = Attendance::updateOrCreate(
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

        return $attendance;
    }

    /**
     * Persist a full attendance batch for one instructor-owned group.
     *
     * Status values are whitelisted against AttendanceStatus before any write;
     * rows with a blank status are deleted, the rest are upserted. All rows are
     * validated up-front so a tampered batch never produces a partial save.
     *
     * @param  array<string, string|null>  $attendanceData  Keyed "{preinscriptionId}_{sessionId}"
     *
     * @throws InvalidArgumentException When the batch references rows outside the group or an invalid status.
     */
    public function registerManualBatch(Group $group, array $attendanceData): void
    {
        $validStatuses = array_map(
            fn (AttendanceStatus $status): string => $status->value,
            AttendanceStatus::cases(),
        );

        $sessionIds = Session::where('group_id', $group->id)->pluck('id')->flip();
        $preinscriptionIds = Preinscription::where('group_id', $group->id)->pluck('id')->flip();

        $stage = [];

        foreach ($attendanceData as $key => $status) {
            $parts = explode('_', (string) $key);

            if (count($parts) !== 2 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
                throw new InvalidArgumentException('Fila de asistencia mal formada.');
            }

            [$preinscriptionId, $sessionId] = [$parts[0], $parts[1]];

            if (! isset($sessionIds[(int) $sessionId]) || ! isset($preinscriptionIds[(int) $preinscriptionId])) {
                throw new InvalidArgumentException('Fila de asistencia ajena al grupo seleccionado.');
            }

            if ($status !== null && $status !== '' && ! in_array($status, $validStatuses, true)) {
                throw new InvalidArgumentException('Estado de asistencia no válido.');
            }

            $stage[(int) $sessionId][(int) $preinscriptionId] = $status;
        }

        foreach ($stage as $sessionId => $rows) {
            foreach ($rows as $preinscriptionId => $status) {
                if ($status === null || $status === '') {
                    Attendance::where('session_id', $sessionId)
                        ->where('preinscription_id', $preinscriptionId)
                        ->delete();
                } else {
                    Attendance::updateOrCreate(
                        [
                            'session_id' => $sessionId,
                            'preinscription_id' => $preinscriptionId,
                        ],
                        ['status' => AttendanceStatus::from($status)],
                    );
                }
            }
        }
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

        $attendance = Attendance::updateOrCreate(
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

        return $attendance;
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
