<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\Log;

class PreinscriptionObserver
{
    public function created(Preinscription $preinscription): void
    {
        $this->evaluateEnablement($preinscription->group_id);
    }

    public function updated(Preinscription $preinscription): void
    {
        if (! $preinscription->wasChanged(['status', 'group_id'])) {
            return;
        }

        $previousGroupId = $preinscription->getOriginal('group_id');
        $currentGroupId = $preinscription->group_id;

        $this->evaluateEnablement($previousGroupId);

        if ($previousGroupId !== $currentGroupId) {
            $this->evaluateEnablement($currentGroupId);
        }
    }

    protected function evaluateEnablement(?int $groupId): void
    {
        if ($groupId === null) {
            return;
        }

        $group = Group::find($groupId);

        if ($group === null || $group->status !== GroupStatus::NO_HABILITADO) {
            return;
        }

        $confirmed = $group->preinscriptions()
            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ->count();

        $threshold = $group->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;

        if ($confirmed < $threshold) {
            return;
        }

        $group->status = GroupStatus::HABILITADO;
        $group->save();

        Log::info('Group auto-enabled after reaching minimum capacity.', [
            'group_id' => $group->id,
            'confirmed' => $confirmed,
            'cupo_minimo' => $threshold,
        ]);
    }
}
