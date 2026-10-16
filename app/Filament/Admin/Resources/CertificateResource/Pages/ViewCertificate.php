<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCertificate extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    public function getTitle(): string
    {
        return "{$this->record->nombre} — Grupos y Certificados";
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Información del Curso')
                ->schema([
                    Infolists\Components\TextEntry::make('nombre')
                        ->label('Curso'),
                    Infolists\Components\TextEntry::make('carga_horaria')
                        ->label('Carga Horaria')
                        ->suffix(' horas'),
                    Infolists\Components\TextEntry::make('instructor.name')
                        ->label('Docente')
                        ->placeholder('Sin asignar'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Estado')
                        ->badge(),
                ])
                ->columns(4),
        ]);
    }

    protected function resolveRecord(int|string $key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)->loadCount('certificates');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('imprimirCertificados')
                ->label('Imprimir Certificados del Curso')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('admin.cursos.certificados.zip', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ($this->record->certificates_count ?? 0) > 0),
        ];
    }
}
