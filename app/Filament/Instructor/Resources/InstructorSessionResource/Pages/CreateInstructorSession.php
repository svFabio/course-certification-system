<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructorSession extends CreateRecord
{
    protected static string $resource = InstructorSessionResource::class;
}
