<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Course;
use App\Services\CourseDemandService;
use App\Services\CourseInterestService;
use App\Support\BusinessRules;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CourseInterestOverviewWidget extends BaseWidget
{
    protected static ?string $heading = 'Registro de Interesados — Cursos sin cupo';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(app(CourseDemandService::class)->coursesWithInterest())
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Curso')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('active_interests_count')
                    ->label('Interesados')
                    ->alignCenter()
                    ->state(fn (Course $record): string => "{$record->active_interests_count} / ".BusinessRules::MIN_GROUP_CAPACITY.' mínimos')
                    ->color(fn (Course $record): string => $this->reachedThreshold($record) ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('umbral')
                    ->label('Umbral')
                    ->alignCenter()
                    ->badge()
                    ->state(fn (Course $record): string => $this->reachedThreshold($record) ? 'Mínimo alcanzado' : 'Bajo el mínimo')
                    ->color(fn (Course $record): string => $this->reachedThreshold($record) ? 'success' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('notificarInteresados')
                    ->label('Notificar interesados')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Notificar a los interesados')
                    ->modalDescription(function (Course $record): string {
                        $count = (int) $record->active_interests_count;

                        if (! $this->reachedThreshold($record)) {
                            return "Atención: solo hay {$count} interesado(s), por debajo del mínimo de ".BusinessRules::MIN_GROUP_CAPACITY.' para abrir un grupo. Puede continuar con la notificación igualmente.';
                        }

                        return "Se notificará a {$count} interesado(s) que hay cupo disponible.";
                    })
                    ->action(function (Course $record): void {
                        $count = (int) $record->active_interests_count;

                        if (! $this->reachedThreshold($record)) {
                            Notification::make()
                                ->title('Umbral mínimo no alcanzado')
                                ->body('Solo hay '.$count.' interesado(s) para este curso (mínimo '.BusinessRules::MIN_GROUP_CAPACITY.'). Se notificará a pesar de ello.')
                                ->warning()
                                ->send();
                        }

                        $notified = app(CourseInterestService::class)->notifyInterested($record);

                        Notification::make()
                            ->title($notified > 0 ? "Se notificó a {$notified} interesado(s)." : 'No hay interesados pendientes de notificar.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Sin interesados registrados')
            ->emptyStateDescription('Los registros de interés aparecerán aquí cuando un estudiante pida ser avisado por un curso sin cupo.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }

    private function reachedThreshold(Course $record): bool
    {
        return app(CourseDemandService::class)->interestThresholdReached((int) $record->active_interests_count);
    }
}
