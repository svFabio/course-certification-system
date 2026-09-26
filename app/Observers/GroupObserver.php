<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Group;

class GroupObserver
{
    /**
     * The planilla headers embed group name, classroom and schedule; refresh
     * the sheets when those change, and refresh the neighbours after the
     * fusion action moves students between groups.
     */
    public function updated(Group $group): void
    {
        if ($group->isDirty(['nombre', 'aula', 'hora_inicio', 'hora_fin', 'cupo_maximo'])) {
            GroupSheetsNeedRefresh::dispatch($group->id);
        }
    }
}
