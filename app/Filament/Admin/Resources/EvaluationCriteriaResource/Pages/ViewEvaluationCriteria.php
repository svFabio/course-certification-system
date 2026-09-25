<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EvaluationCriteriaResource\Pages;

use App\Filament\Admin\Resources\EvaluationCriteriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvaluationCriteria extends ViewRecord
{
    protected static string $resource = EvaluationCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
