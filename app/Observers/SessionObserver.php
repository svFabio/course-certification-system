<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Session;

class SessionObserver
{
    public function created(Session $session): void
    {
        GroupSheetsNeedRefresh::dispatch($session->group_id);
    }

    public function updated(Session $session): void
    {
        if ($session->isDirty(['fecha', 'hora_inicio', 'hora_fin', 'group_id'])) {
            GroupSheetsNeedRefresh::dispatch($session->group_id);
        }
    }

    public function deleted(Session $session): void
    {
        GroupSheetsNeedRefresh::dispatch($session->group_id);
    }
}
