<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Preinscription;
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
        ]);

        $sessionId = $request->input('session_id');
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

        $preinscription = $this->findPreinscription($request, $group);

        if (! $preinscription) {
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
            'status' => $isWithinRadius ? 'presente' : 'para_revision',
            'attendance_status' => $attendance->status->value,
            'distance_metros' => $attendance->distancia_metros,
            'max_distance' => Config::get('attendance.radius_meters'),
            'attendance' => $attendance,
        ]);
    }

    private function findPreinscription(Request $request, $group): ?Preinscription
    {
        if ($request->filled('preinscription_id')) {
            return Preinscription::where('group_id', $group->id)
                ->where('id', $request->input('preinscription_id'))
                ->first();
        }

        if ($request->filled('ci')) {
            return Preinscription::where('group_id', $group->id)
                ->where('ci', $request->input('ci'))
                ->first();
        }

        if ($request->user()) {
            return Preinscription::where('group_id', $group->id)
                ->where('email', $request->user()->email)
                ->first();
        }

        return null;
    }
}
