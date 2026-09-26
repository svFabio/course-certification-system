<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Admin\Resources\CourseResource;
use App\Filament\Concerns\HasBackButton;
use App\Services\CertificateService;
use App\Services\CourseService;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewCourse extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('cerrarCurso')
                ->label('Cerrar curso y generar certificados')
                ->icon('heroicon-o-document-check')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cerrar curso y emitir certificados')
                ->modalDescription('Se marcará el curso como finalizado y se generarán certificados digitales (estudiantes inscritos + instructor). Quedarán en estado «Pendiente de firma».')
                ->visible(fn (): bool => app(CourseService::class)->canClose($this->record))
                ->action(function (CourseService $courseService): void {
                    try {
                        $result = $courseService->close($this->record);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo cerrar el curso')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();

                        return;
                    }

                    $count = $result['certificates']->count();

                    Notification::make()
                        ->title('Curso cerrado correctamente')
                        ->body($count === 0
                            ? 'No había certificados pendientes por generar.'
                            : "Se generaron {$count} certificado(s) en estado pendiente de firma.")
                        ->success()
                        ->actions([
                            NotificationAction::make('verCertificados')
                                ->label('Ver certificados')
                                ->url(CertificateResource::getUrl('index')),
                        ])
                        ->send();

                    $this->record->refresh();
                }),
            Actions\Action::make('generarCertificadosFaltantes')
                ->label('Generar certificados faltantes')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generar certificados pendientes')
                ->modalDescription('Se emitirán certificados para estudiantes inscritos e instructor que aún no tengan uno (instructor: un certificado consolidado con todos los grupos).')
                ->visible(fn (): bool => $this->record->status === CourseStatus::FINALIZADO)
                ->action(function (CertificateService $certificateService): void {
                    $created = $certificateService->generateForCourse($this->record);
                    $count = $created->count();

                    Notification::make()
                        ->title($count === 0 ? 'Sin certificados nuevos' : 'Certificados generados')
                        ->body($count === 0
                            ? 'Todos los beneficiarios ya tienen certificado.'
                            : "Se generaron {$count} certificado(s) pendientes de firma.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('certificadoInstructorPorGrupo')
                ->label('Certificado instructor por grupo')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Emitir certificado del instructor por cada grupo')
                ->modalDescription('Genera un certificado de instructor por cada grupo del curso (además del consolidado, si ya existiera). Solo crea los que falten.')
                ->visible(fn (): bool => $this->record->status === CourseStatus::FINALIZADO && $this->record->instructor_id !== null)
                ->action(function (CertificateService $certificateService): void {
                    $created = $certificateService->generateInstructorCertificates($this->record, onePerGroup: true);
                    $count = $created->count();

                    Notification::make()
                        ->title($count === 0 ? 'Sin certificados nuevos' : 'Certificados de instructor generados')
                        ->body($count === 0
                            ? 'Ya existen certificados de instructor por grupo.'
                            : "Se generaron {$count} certificado(s) de instructor pendientes de firma.")
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}
