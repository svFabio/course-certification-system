<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AttendanceScanResult;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'preinscription_id' => 'nullable|integer',
            'ci' => 'nullable|string',
        ], [
            'lat.numeric' => 'La latitud debe ser un valor numérico.',
            'lat.between' => 'La latitud debe estar entre -90 y 90.',
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

        $lat = isset($validated['lat']) && $validated['lat'] !== null ? (float) $validated['lat'] : null;
        $lng = isset($validated['lng']) && $validated['lng'] !== null ? (float) $validated['lng'] : null;

        $attendance = $this->attendanceService->registerFromQR(
            $session,
            $preinscription->id,
            $lat,
            $lng,
        );

        return response()->json([
            'message' => 'Asistencia registrada correctamente.',
            'status' => AttendanceScanResult::PRESENTE->value,
            'attendance_status' => $attendance->status->value,
            'attendance' => $attendance,
        ]);
    }
}
