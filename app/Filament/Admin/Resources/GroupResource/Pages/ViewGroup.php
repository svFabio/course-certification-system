<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GroupResource\Pages;

use App\Filament\Admin\Resources\GroupResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewGroup extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = GroupResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)->loadCount('certificates');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('imprimirCertificados')
                ->label('Imprimir Certificados del Grupo')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('admin.grupos.certificados.zip', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ($this->record->certificates_count ?? 0) > 0),
            Actions\EditAction::make(),
        ];
    }
}
