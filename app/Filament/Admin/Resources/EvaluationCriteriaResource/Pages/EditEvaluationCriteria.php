<?php
declare(strict_types=1);

namespace App\Filament\Admin\Resources\EvaluationCriteriaResource\Pages;

use App\Filament\Admin\Resources\EvaluationCriteriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluationCriteria extends EditRecord
{
    protected static string $resource = EvaluationCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
