<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EvaluationCriteriaResource\Pages;

use App\Filament\Admin\Resources\EvaluationCriteriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluationCriteria extends CreateRecord
{
    protected static string $resource = EvaluationCriteriaResource::class;
}
