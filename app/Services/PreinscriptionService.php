<?php

declare(strict_types=1);

namespace App\Services;

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

        if (!$this->hasAvailableCapacity($group)) {
            throw ValidationException::withMessages([
                'group_id' => 'The selected group has reached its maximum capacity.',
            ]);
        }

        return Preinscription::create([
            'group_id' => $group->id,
            'ci' => $data['ci'],
            'nombres' => $data['nombres'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'celular' => $data['celular'] ?? null,
            'email' => $data['email'],
            'tipo_participante' => $data['tipo_participante'],
            'status' => PreinscriptionStatus::PENDIENTE_PAGO,
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

    public static function getAvailableGroups(int $courseId): Collection
    {
        return Group::where('course_id', $courseId)
            ->where('status', 'habilitado')
            ->withCount(['preinscriptions as confirmed_count' => fn ($q) =>
                $q->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ])
            ->get()
            ->filter(fn ($group) => $group->confirmed_count < $group->cupo_maximo);
    }

    public function processPaymentConfirmation(Preinscription $preinscription): Preinscription
    {
        $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

        return $preinscription->fresh();
    }
}
