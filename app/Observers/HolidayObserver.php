<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\GroupStatus;
use App\Models\Holiday;
use App\Models\Session;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class HolidayObserver
{
    public function created(Holiday $holiday): void
    {
        $affectedSessions = Session::query()
            ->whereDate('fecha', $holiday->fecha)
            ->whereDate('fecha', '>=', today())
            ->whereHas('group', function (Builder $query): void {
                $query->where('status', '!=', GroupStatus::CERRADO->value);
            })
            ->count();

        if ($affectedSessions === 0) {
            return;
        }

        Log::warning('Holiday registered with scheduled sessions on the same date.', [
            'holiday_id' => $holiday->id,
            'holiday_name' => $holiday->nombre,
            'fecha' => $holiday->fecha->toDateString(),
            'affected_sessions' => $affectedSessions,
        ]);

        Notification::make()
            ->title("Feriado registrado: {$holiday->nombre}")
            ->body("Se detectaron {$affectedSessions} clase(s) programadas para {$holiday->fecha->format('d/m/Y')} en grupos activos. Reprogramélas desde cada sesión.")
            ->warning()
            ->send();
    }
}
