<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreinscriptionService
{
    public function register(array $data, bool $enforceEnabledGroup = true): Preinscription
    {
        return DB::transaction(function () use ($data, $enforceEnabledGroup) {
            $group = Group::where('id', $data['group_id'])->lockForUpdate()->firstOrFail();

            if ($enforceEnabledGroup && $group->status !== GroupStatus::HABILITADO) {
                throw ValidationException::withMessages([
                    'group_id' => 'El grupo seleccionado no está habilitado para recibir preinscripciones.',
                ]);
            }

            $activeExists = Preinscription::where('ci', $data['ci'])
                ->whereHas('group', fn ($q) => $q->where('course_id', $group->course_id))
                ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'ci' => 'Ya enviaste tu preinscripción para este curso. Por favor realiza el pago y mantente atento(a) a tu teléfono o correo para confirmar tu inscripción.',
                ]);
            }

            $priorSameGroup = Preinscription::where('ci', $data['ci'])
                ->where('group_id', $group->id)
                ->first();

            if ($priorSameGroup !== null) {
                throw ValidationException::withMessages([
                    'ci' => $this->describePriorSameGroupPreinscription($priorSameGroup->status),
                ]);
            }

            if (! $this->hasAvailableCapacity($group)) {
                throw ValidationException::withMessages([
                    'group_id' => 'El grupo seleccionado ha alcanzado su capacidad máxima.',
                ]);
            }

            return Preinscription::create([
                'group_id' => $group->id,
                'ci' => $data['ci'],
                'cod_sis' => $data['cod_sis'] ?? null,
                'nombres' => $data['nombres'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'] ?? null,
                'celular' => $data['celular'] ?? null,
                'email' => $data['email'],
                'tipo_participante' => $data['tipo_participante'],
                'status' => PreinscriptionStatus::PENDIENTE_PAGO,
                'fotocopia_ci' => (bool) ($data['fotocopia_ci'] ?? false),
                'auxiliar_certificado_path' => $data['auxiliar_certificado_path'] ?? null,
            ]);
        });
    }

    private function describePriorSameGroupPreinscription(PreinscriptionStatus $status): string
    {
        return match ($status) {
            PreinscriptionStatus::RECHAZADO => 'Este CI ya tiene una preinscripción rechazada para este grupo. No es posible registrar una nueva solicitud automáticamente; contacta a la coordinación del curso.',
            PreinscriptionStatus::RETIRADO => 'Este CI ya tiene una preinscripción retirada para este grupo. Contacta a la coordinación del curso si deseas participar nuevamente.',
            default => 'Este CI ya tiene una preinscripción registrada para este grupo. Contacta a la coordinación del curso para más información.',
        };
    }

    public function hasAvailableCapacity(Group $group): bool
    {
        $confirmed = $group->preinscriptions()
            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ->count();

        return $confirmed < $group->cupo_maximo;
    }

    public function markAsWithdrawn(Preinscription $preinscription): void
    {
        if (! in_array($preinscription->status, [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO], true)) {
            throw ValidationException::withMessages([
                'status' => 'Solo se puede retirar a participantes en estado pendiente de pago o inscrito.',
            ]);
        }

        $preinscription->update(['status' => PreinscriptionStatus::RETIRADO]);

        if ($preinscription->group_id !== null) {
            GroupSheetsNeedRefresh::dispatch($preinscription->group_id);
        }
    }

    public function moveToGroup(Preinscription $preinscription, Group $destination): Preinscription
    {
        if ($preinscription->group_id === $destination->id) {
            throw ValidationException::withMessages([
                'group_id' => 'El participante ya está asignado a ese grupo.',
            ]);
        }

        if ($preinscription->group->course_id !== $destination->course_id) {
            throw ValidationException::withMessages([
                'group_id' => 'El grupo de destino debe pertenecer al mismo curso.',
            ]);
        }

        if ($destination->status !== GroupStatus::HABILITADO) {
            throw ValidationException::withMessages([
                'group_id' => 'El grupo de destino no está habilitado.',
            ]);
        }

        if (! $this->hasAvailableCapacity($destination)) {
            throw ValidationException::withMessages([
                'group_id' => 'El grupo de destino ha alcanzado su capacidad máxima.',
            ]);
        }

        $duplicate = Preinscription::where('ci', $preinscription->ci)
            ->where('group_id', $destination->id)
            ->where('id', '!=', $preinscription->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'group_id' => 'El participante ya está preinscrito en el grupo de destino.',
            ]);
        }

        $previousGroupId = $preinscription->group_id;

        $preinscription->update(['group_id' => $destination->id]);

        if ($previousGroupId !== null) {
            GroupSheetsNeedRefresh::dispatch($previousGroupId);
        }

        GroupSheetsNeedRefresh::dispatch($destination->id);

        return $preinscription->fresh();
    }

    public function getAvailableGroups(int $courseId): Collection
    {
        return Group::where('course_id', $courseId)
            ->where('status', GroupStatus::HABILITADO)
            ->withCount(['preinscriptions as confirmed_count' => fn ($q) => $q->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO]),
            ])
            ->get()
            ->filter(fn ($group) => $group->confirmed_count < $group->cupo_maximo);
    }
}
