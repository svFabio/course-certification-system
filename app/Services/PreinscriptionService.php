<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PreinscriptionService
{
    public function register(array $data): Preinscription
    {
        $group = Group::findOrFail($data['group_id']);

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
        ]);
    }

    public function evaluateCupoMinimo(Group $group): bool
    {
        $confirmed = $group->preinscriptions()
            ->where('status', PreinscriptionStatus::INSCRITO)
            ->count();

        return $confirmed >= BusinessRules::MIN_GROUP_CAPACITY;
    }

    public function hasAvailableCapacity(Group $group): bool
    {
        $confirmed = $group->preinscriptions()
            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ->count();

        return $confirmed < $group->cupo_maximo;
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
