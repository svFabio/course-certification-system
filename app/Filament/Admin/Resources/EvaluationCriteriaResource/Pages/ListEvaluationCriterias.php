<?php
declare(strict_types=1);

namespace App\Filament\Admin\Resources\EvaluationCriteriaResource\Pages;

use App\Filament\Admin\Resources\EvaluationCriteriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationCriterias extends ListRecords
{
    protected static string $resource = EvaluationCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
