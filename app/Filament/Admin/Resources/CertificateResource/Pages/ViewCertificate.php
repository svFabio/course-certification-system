<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Certificado')
                ->schema([
                    Infolists\Components\TextEntry::make('beneficiario')
                        ->label('Beneficiario')
                        ->state(fn ($record): string => $record->beneficiaryName()),
                    Infolists\Components\TextEntry::make('course.nombre')->label('Curso'),
                    Infolists\Components\TextEntry::make('tipo')->badge()->label('Tipo'),
                    Infolists\Components\TextEntry::make('grupos_detalle')
                        ->label('Grupo(s)')
                        ->visible(fn ($record): bool => $record->isInstructorCertificate()),
                    Infolists\Components\TextEntry::make('codigo_unico')->label('Código')->copyable(),
                    Infolists\Components\TextEntry::make('signature_status')->badge()->label('Estado de firma'),
                    Infolists\Components\TextEntry::make('demora')
                        ->label('Demora administrativa')
                        ->state(fn ($record): string => $record->administrativeDelayLabel()),
                    Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y H:i')->label('Emitido'),
                    Infolists\Components\TextEntry::make('listo_en')->dateTime('d/m/Y H:i')->label('Listo'),
                ])
                ->columns(2),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
