<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PreinscriptionStatus;
use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use App\Mail\StudentRequestReceivedNotification;
use App\Mail\StudentRequestResolvedNotification;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\StudentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StudentRequestService
{
    public function submit(
        Preinscription $preinscription,
        StudentRequestType $tipo,
        ?int $groupIdDestino,
        string $motivo,
    ): StudentRequest {
        $preinscription->refresh();

        if ($this->hasPendingRequest($preinscription, $tipo)) {
            throw ValidationException::withMessages([
                'tipo' => 'Ya tienes una solicitud pendiente de este tipo para esta preinscripción.',
            ]);
        }

        $this->assertStatusAllows($preinscription, $tipo);

        $destination = $tipo === StudentRequestType::CAMBIO_GRUPO
            ? $this->resolveDestination($preinscription, $groupIdDestino)
            : null;

        $request = StudentRequest::create([
            'preinscription_id' => $preinscription->id,
            'tipo' => $tipo,
            'group_id_destino' => $destination?->id,
            'motivo' => $motivo,
            'estado' => StudentRequestStatus::PENDIENTE,
            'motivo_rechazo' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        Mail::to($preinscription->email)
            ->queue(new StudentRequestReceivedNotification($request));

        return $request;
    }

    public function approve(StudentRequest $request, User $user): StudentRequest
    {
        $preinscription = null;

        DB::transaction(function () use ($request, $user, &$preinscription): void {
            /** @var StudentRequest $locked */
            $locked = StudentRequest::lockForUpdate()->findOrFail($request->getKey());

            if ($locked->estado !== StudentRequestStatus::PENDIENTE) {
                throw ValidationException::withMessages([
                    'estado' => 'La solicitud ya fue resuelta y no puede aprobarse nuevamente.',
                ]);
            }

            $preinscription = $locked->preinscription()->firstOrFail();

            match ($locked->tipo) {
                StudentRequestType::CAMBIO_GRUPO => app(PreinscriptionService::class)
                    ->moveToGroup($preinscription, $this->requireDestination($locked)),
                StudentRequestType::DEVOLUCION => app(PaymentService::class)
                    ->requestRefund($preinscription, $locked->motivo),
            };

            $locked->update([
                'estado' => StudentRequestStatus::APROBADA,
                'motivo_rechazo' => null,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            $request->setRawAttributes($locked->fresh()->getAttributes());
        });

        Mail::to($preinscription->email)
            ->queue(new StudentRequestResolvedNotification($request->fresh()));

        return $request;
    }

    public function reject(StudentRequest $request, string $reason, User $user): StudentRequest
    {
        $preinscriptionEmail = null;

        DB::transaction(function () use ($request, $reason, $user, &$preinscriptionEmail): void {
            /** @var StudentRequest $locked */
            $locked = StudentRequest::lockForUpdate()->findOrFail($request->getKey());

            if ($locked->estado !== StudentRequestStatus::PENDIENTE) {
                throw ValidationException::withMessages([
                    'estado' => 'La solicitud ya fue resuelta y no puede rechazarse nuevamente.',
                ]);
            }

            $locked->loadMissing('preinscription');
            $preinscriptionEmail = $locked->preinscription?->email;

            $locked->update([
                'estado' => StudentRequestStatus::RECHAZADA,
                'motivo_rechazo' => $reason,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            $request->setRawAttributes($locked->fresh()->getAttributes());
        });

        if (filled($preinscriptionEmail)) {
            Mail::to($preinscriptionEmail)
                ->queue(new StudentRequestResolvedNotification($request->fresh()));
        }

        return $request;
    }

    protected function hasPendingRequest(Preinscription $preinscription, StudentRequestType $tipo): bool
    {
        return $preinscription->studentRequests()
            ->where('tipo', $tipo->value)
            ->where('estado', StudentRequestStatus::PENDIENTE->value)
            ->exists();
    }

    protected function assertStatusAllows(Preinscription $preinscription, StudentRequestType $tipo): void
    {
        $allowed = match ($tipo) {
            StudentRequestType::CAMBIO_GRUPO => [
                PreinscriptionStatus::PENDIENTE_PAGO,
                PreinscriptionStatus::INSCRITO,
            ],
            StudentRequestType::DEVOLUCION => [
                PreinscriptionStatus::INSCRITO,
            ],
        };

        if (! in_array($preinscription->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'La preinscripción no se encuentra en un estado que permita esta solicitud.',
            ]);
        }
    }

    protected function resolveDestination(Preinscription $preinscription, ?int $groupIdDestino): Group
    {
        if ($groupIdDestino === null) {
            throw ValidationException::withMessages([
                'group_id_destino' => 'Debes seleccionar un grupo de destino.',
            ]);
        }

        $destination = Group::find($groupIdDestino);

        if ($destination === null) {
            throw ValidationException::withMessages([
                'group_id_destino' => 'El grupo de destino no existe.',
            ]);
        }

        if ($destination->id === $preinscription->group_id) {
            throw ValidationException::withMessages([
                'group_id_destino' => 'El grupo de destino debe ser distinto al grupo actual.',
            ]);
        }

        if ($preinscription->group?->course_id !== $destination->course_id) {
            throw ValidationException::withMessages([
                'group_id_destino' => 'El grupo de destino debe pertenecer al mismo curso.',
            ]);
        }

        return $destination;
    }

    protected function requireDestination(StudentRequest $request): Group
    {
        $destination = $request->destinationGroup()->first();

        if ($destination === null) {
            throw ValidationException::withMessages([
                'group_id_destino' => 'La solicitud no tiene un grupo de destino válido.',
            ]);
        }

        return $destination;
    }
}
