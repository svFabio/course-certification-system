<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupMergeService
{
    public function __construct(
        private readonly PreinscriptionService $preinscriptions,
    ) {}

    /**
     * Merges a course group into a destination group of the same course.
     *
     * Moves every active preinscription (pendiente_pago, inscrito) from the
     * origin to the destination, closes the origin and returns the moved
     * participants so the caller can dispatch refreshes and notifications.
     *
     * @return array{origin: Group, destination: Group, toMove: Collection<int, Preinscription>, movedCount: int}
     */
    public function merge(Group $origin, Group $destinationGroup): array
    {
        return DB::transaction(function () use ($origin, $destinationGroup): array {
            $origin = Group::query()->whereKey($origin->getKey())->lockForUpdate()->firstOrFail();
            $destination = Group::query()->whereKey($destinationGroup->getKey())->lockForUpdate()->firstOrFail();

            if ($destination->getKey() === $origin->getKey()
                || $destination->course_id !== $origin->course_id
                || $destination->status === GroupStatus::CERRADO
            ) {
                throw ValidationException::withMessages([
                    'grupo_destino_id' => 'Seleccione un grupo destino válido del mismo curso que no esté cerrado.',
                ]);
            }

            $activeStatuses = [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO];

            $toMove = $origin->preinscriptions()->whereIn('status', $activeStatuses)->get();
            $movedCount = $toMove->count();

            if ($this->preinscriptions->availableSlots($destination) < $movedCount) {
                throw ValidationException::withMessages([
                    'grupo_destino_id' => "El grupo destino no tiene cupo disponible para {$movedCount} participante(s).",
                ]);
            }

            if ($movedCount > 0) {
                foreach ($toMove as $preinscription) {
                    $preinscription->update(['group_id' => $destination->getKey()]);
                }
            }

            $origin->status = GroupStatus::CERRADO;
            $origin->save();

            return [
                'origin' => $origin,
                'destination' => $destination,
                'toMove' => $toMove,
                'movedCount' => $movedCount,
            ];
        });
    }
}
