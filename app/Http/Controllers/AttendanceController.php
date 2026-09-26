<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AttendanceScanResult;
use App\Enums\AttendanceStatus;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_code' => 'required_without:session_id|string',
            'session_id' => 'required_without:session_code|integer',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'preinscription_id' => 'nullable|integer',
            'ci' => 'nullable|string',
        ], [
            'lat.required' => 'La latitud es obligatoria.',
            'lat.numeric' => 'La latitud debe ser un valor numérico.',
            'lat.between' => 'La latitud debe estar entre -90 y 90.',
            'lng.required' => 'La longitud es obligatoria.',
            'lng.numeric' => 'La longitud debe ser un valor numérico.',
            'lng.between' => 'La longitud debe estar entre -180 y 180.',
        ]);

        $sessionId = isset($validated['session_id']) ? (int) $validated['session_id'] : null;
        $sessionCode = (string) $request->input('session_code', '');

        $session = $this->attendanceService->findSession($sessionId, $sessionCode);

        if (! $session) {
            return response()->json([
                'message' => 'Sesión no encontrada.',
            ], 404);
        }

        $group = $session->group;
        if (! $group) {
            return response()->json([
                'message' => 'Grupo no encontrado para la sesión.',
            ], 404);
        }

        if (! $this->attendanceService->canManageSession($session, $request->user())) {
            return response()->json([
                'message' => 'No tiene permisos para registrar asistencia en esta sesión.',
            ], 403);
        }

        $preinscription = $this->attendanceService->findPreinscription(
            [
                'preinscription_id' => $request->input('preinscription_id'),
                'ci' => $request->input('ci'),
                'email' => $request->user()?->email,
            ],
            $group->id
        );

        if (! $preinscription || $preinscription->group_id !== $group->id) {
            return response()->json([
                'message' => 'No se encontró la preinscripción del participante para esta sesión.',
            ], 422);
        }

        $attendance = $this->attendanceService->registerFromQR(
            $session,
            $preinscription->id,
            (float) $validated['lat'],
            (float) $validated['lng'],
        );

        $isWithinRadius = $attendance->status === AttendanceStatus::PRESENTE;

        return response()->json([
            'message' => $isWithinRadius
                ? 'Asistencia registrada correctamente.'
                : 'Fuera de rango. Su registro será enviado para revisión.',
            'status' => $isWithinRadius ? AttendanceScanResult::PRESENTE->value : AttendanceScanResult::PARA_REVISION->value,
            'attendance_status' => $attendance->status->value,
            'distance_metros' => $attendance->distancia_metros,
            'max_distance' => Config::get('attendance.radius_meters'),
            'attendance' => $attendance,
        ]);
    }
}
