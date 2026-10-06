<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use App\Filament\Admin\Resources\StudentRequestResource\Pages;
use App\Models\StudentRequest;
use App\Services\StudentRequestService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class StudentRequestResource extends Resource
{
    protected static ?string $model = StudentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Inscripciones';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static ?string $navigationLabel = 'Solicitudes de estudiantes';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['preinscription.group.course', 'destinationGroup', 'resolvedBy']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('preinscription.full_name')
                    ->label('Estudiante')
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno']),
                Tables\Columns\TextColumn::make('preinscription.group.course.nombre')
                    ->label('Curso')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('resumen_grupos')
                    ->label('Grupo actual → destino')
                    ->state(function (StudentRequest $record): string {
                        $actual = $record->preinscription?->group?->nombre ?? '—';
                        $destino = $record->tipo === StudentRequestType::CAMBIO_GRUPO
                            ? ($record->destinationGroup?->nombre ?? '—')
                            : '—';

                        return "{$actual} → {$destino}";
                    }),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(StudentRequestStatus::class)
                    ->default(StudentRequestStatus::PENDIENTE->value),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StudentRequest $record): bool => $record->estado === StudentRequestStatus::PENDIENTE)
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar solicitud')
                    ->modalDescription('Se aplicará el cambio solicitado (cambio de grupo o devolución) y se notificará al estudiante.')
                    ->action(function (StudentRequest $record): void {
                        try {
                            app(StudentRequestService::class)->approve($record, auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo aprobar la solicitud.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Solicitud aprobada y estudiante notificado.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StudentRequest $record): bool => $record->estado === StudentRequestStatus::PENDIENTE)
                    ->modalHeading('Rechazar solicitud')
                    ->modalDescription('El estudiante recibirá el motivo indicado por correo.')
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (StudentRequest $record, array $data): void {
                        try {
                            app(StudentRequestService::class)->reject($record, $data['motivo_rechazo'], auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo rechazar la solicitud.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Solicitud rechazada y estudiante notificado.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No hay solicitudes pendientes')
            ->emptyStateDescription('Cuando un estudiante solicite un cambio de grupo o una devolución, aparecerá aquí.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentRequests::route('/'),
        ];
    }
}
