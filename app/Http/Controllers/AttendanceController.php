<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Preinscription;
use App\Models\Session;
use App\Services\AttendanceService;
use App\Support\BusinessRules;
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
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'preinscription_id' => 'nullable|integer',
            'ci' => 'nullable|string',
        ]);

        $sessionId = $request->input('session_id');
        $sessionCode = (string) $request->input('session_code', '');

        $session = null;
        if ($sessionId) {
            $session = Session::with('group')->find($sessionId);
        } elseif (is_numeric($sessionCode)) {
            $session = Session::with('group')->find((int) $sessionCode);
        } elseif (preg_match('/(\d+)/', $sessionCode, $matches)) {
            $session = Session::with('group')->find((int) $matches[1]);
        }

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

        $preinscription = null;
        if ($request->filled('preinscription_id')) {
            $preinscription = Preinscription::where('group_id', $group->id)
                ->where('id', $request->input('preinscription_id'))
                ->first();
        } elseif ($request->filled('ci')) {
            $preinscription = Preinscription::where('group_id', $group->id)
                ->where('ci', $request->input('ci'))
                ->first();
        } elseif ($request->user()) {
            $preinscription = Preinscription::where('group_id', $group->id)
                ->where('email', $request->user()->email)
                ->first();
        }

        if (! $preinscription) {
            return response()->json([
                'message' => 'No se encontró la preinscripción del participante para esta sesión.',
            ], 422);
        }

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $labLat = (float) env('LAB_LAT', -17.7833);
        $labLng = (float) env('LAB_LNG', -66.1500);
        $maxRadius = (float) env('ATTENDANCE_RADIUS_METERS', BusinessRules::MAX_ATTENDANCE_DISTANCE_METERS);

        $distance = BusinessRules::haversineDistance($lat, $lng, $labLat, $labLng);
        $isWithinRadius = $distance <= $maxRadius;

        $dbStatus = $isWithinRadius ? AttendanceStatus::PRESENTE : AttendanceStatus::AUSENTE;
        $responseStatus = $isWithinRadius ? 'presente' : 'para_revision';

        $attendance = Attendance::updateOrCreate(
            [
                'session_id' => $session->id,
                'preinscription_id' => $preinscription->id,
            ],
            [
                'status' => $dbStatus,
                'lat' => $lat,
                'lng' => $lng,
                'distancia_metros' => round($distance, 2),
            ]
        );

        return response()->json([
            'message' => $isWithinRadius ? 'Asistencia registrada correctamente.' : 'Fuera de rango. Su registro será enviado para revisión.',
            'status' => $responseStatus,
            'attendance_status' => $dbStatus->value,
            'distance_metros' => round($distance, 2),
            'max_distance' => $maxRadius,
            'attendance' => $attendance,
        ]);
    }
}
