<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\CourseResource;
use App\Services\CourseService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publicar')
                ->label('Publicar')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === CourseStatus::EN_PREPARACION)
                ->modalHeading('Publicar curso para preinscripción')
                ->modalDescription('Confirme la fecha de inicio y fin del periodo de preinscripción.')
                ->modalSubmitActionLabel('Confirmar publicación')
                ->form([
                    Forms\Components\DatePicker::make('fecha_inicio_preinscripcion')
                        ->label('Inicio de preinscripción')
                        ->required()
                        ->native(false)
                        ->default(now()),
                    Forms\Components\DatePicker::make('fecha_fin_preinscripcion')
                        ->label('Fin de preinscripción')
                        ->required()
                        ->native(false)
                        ->minDate(now())
                        ->default(now()->addDays(14)),
                ])
                ->action(function (array $data, CourseService $service) {
                    try {
                        $this->record = $service->publish(
                            $this->record,
                            $data['fecha_inicio_preinscripcion'],
                            $data['fecha_fin_preinscripcion'],
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title($exception->validator->errors()->first())
                            ->danger()
                            ->send();

                        $this->halt();
                    }

                    Notification::make()
                        ->title('Curso publicado')
                        ->body('Ya aparece en el catálogo público. Se descargará el resumen para redes y WhatsApp.')
                        ->success()
                        ->send();

                    return response()->streamDownload(
                        fn () => print($service->publicationSummary($this->record)),
                        'resumen-curso-'.Str::slug($this->record->nombre).'.txt',
                        ['Content-Type' => 'text/plain; charset=UTF-8'],
                    );
                }),
            Actions\Action::make('cerrarPreinscripcion')
                ->label('Cerrar preinscripción')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === CourseStatus::PUBLICADO)
                ->requiresConfirmation()
                ->modalHeading('Cerrar preinscripción')
                ->modalDescription('El curso dejará de aceptar nuevas preinscripciones y pasará a estado Preinscripción cerrada.')
                ->modalSubmitActionLabel('Cerrar ahora')
                ->action(function (CourseService $service) {
                    try {
                        $this->record = $service->closePreinscription($this->record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title($exception->validator->errors()->first())
                            ->danger()
                            ->send();

                        $this->halt();
                    }

                    Notification::make()
                        ->title('Preinscripción cerrada')
                        ->body('Se descargará el resumen de cupos alcanzados por grupo.')
                        ->success()
                        ->send();

                    return response()->streamDownload(
                        fn () => print($service->cuposSummary($this->record)),
                        'resumen-cupos-'.Str::slug($this->record->nombre).'.txt',
                        ['Content-Type' => 'text/plain; charset=UTF-8'],
                    );
                }),
            Actions\Action::make('descargarResumen')
                ->label('Descargar resumen')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => in_array($this->record->status, [
                    CourseStatus::PUBLICADO,
                    CourseStatus::PREINSCRIPCION_CERRADA,
                ], true))
                ->action(function (CourseService $service) {
                    $isClosed = $this->record->status === CourseStatus::PREINSCRIPCION_CERRADA;
                    $content = $isClosed
                        ? $service->cuposSummary($this->record)
                        : $service->publicationSummary($this->record);
                    $prefix = $isClosed ? 'resumen-cupos-' : 'resumen-curso-';

                    return response()->streamDownload(
                        fn () => print($content),
                        $prefix.Str::slug($this->record->nombre).'.txt',
                        ['Content-Type' => 'text/plain; charset=UTF-8'],
                    );
                }),
            Actions\EditAction::make(),
        ];
    }
}
