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
        $this->evaluateEnablement($preinscription->group_id, allowDemotion: false);
    }

    public function updated(Preinscription $preinscription): void
    {
        if (! $preinscription->wasChanged(['status', 'group_id'])) {
            return;
        }

        $previousGroupId = $preinscription->getOriginal('group_id');
        $currentGroupId = $preinscription->group_id;

        $this->evaluateEnablement($previousGroupId, allowDemotion: true);

        if ($previousGroupId !== $currentGroupId) {
            $this->evaluateEnablement($currentGroupId, allowDemotion: false);
        }
    }

    public function deleted(Preinscription $preinscription): void
    {
        $this->evaluateEnablement($preinscription->group_id, allowDemotion: true);
    }

    protected function evaluateEnablement(int|string|null $groupId, bool $allowDemotion = true): void
    {
        if ($groupId === null) {
            return;
        }

        $group = Group::find((int) $groupId);

        if ($group === null) {
            return;
        }

        $threshold = $group->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;
        $confirmed = $group->preinscriptions()
            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ->count();

        if ($group->status === GroupStatus::NO_HABILITADO && $confirmed >= $threshold) {
            $group->status = GroupStatus::HABILITADO;
            $group->save();

            Log::info('Group auto-enabled after reaching minimum capacity.', [
                'group_id' => $group->id,
                'confirmed' => $confirmed,
                'cupo_minimo' => $threshold,
            ]);

            return;
        }

        if ($allowDemotion && $group->status === GroupStatus::HABILITADO && $confirmed < $threshold) {
            $group->status = GroupStatus::NO_HABILITADO;
            $group->save();

            Log::info('Group auto-disabled after falling below minimum capacity.', [
                'group_id' => $group->id,
                'confirmed' => $confirmed,
                'cupo_minimo' => $threshold,
            ]);
        }
    }
}
