<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources;

use App\Enums\PreinscriptionStatus;
use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentPreinscriptionResource\Pages;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\StudentRequestService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StudentPreinscriptionResource extends Resource
{
    protected static ?string $model = Preinscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Mi formación';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Preinscripción';

    protected static ?string $pluralModelLabel = 'Mis preinscripciones';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::STUDENT->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->check() && $record->email === auth()->user()?->email;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('email', auth()->user()?->email)
            ->with(['group.course'])
            ->withCount([
                'studentRequests as pending_cambio_count' => fn ($q) => $q
                    ->where('tipo', StudentRequestType::CAMBIO_GRUPO->value)
                    ->where('estado', StudentRequestStatus::PENDIENTE->value),
                'studentRequests as pending_devolucion_count' => fn ($q) => $q
                    ->where('tipo', StudentRequestType::DEVOLUCION->value)
                    ->where('estado', StudentRequestStatus::PENDIENTE->value),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('group.course.nombre')->label('Curso'),
            Infolists\Components\TextEntry::make('group.nombre')->label('Grupo'),
            Infolists\Components\TextEntry::make('status')->badge()->label('Estado'),
            Infolists\Components\TextEntry::make('tipo_participante')->label('Tipo de participante'),
            Infolists\Components\TextEntry::make('ci')->label('CI'),
            Infolists\Components\TextEntry::make('email'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group.course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.nombre')
                    ->label('Grupo'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('tipo_participante')
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Fecha'),
            ])
            ->actions([
                Tables\Actions\Action::make('solicitarCambioGrupo')
                    ->label('Solicitar cambio de grupo')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (Preinscription $record): bool => in_array($record->status, [
                        PreinscriptionStatus::PENDIENTE_PAGO,
                        PreinscriptionStatus::INSCRITO,
                    ], true) && ($record->pending_cambio_count ?? 0) === 0)
                    ->modalHeading('Solicitar cambio de grupo')
                    ->modalDescription('La coordinación del curso revisará tu solicitud. El cambio no es inmediato.')
                    ->form([
                        Forms\Components\Select::make('group_id_destino')
                            ->label('Grupo de destino')
                            ->options(fn (Preinscription $record): array => Group::query()
                                ->where('course_id', $record->group->course_id)
                                ->whereKeyNot($record->group_id)
                                ->withConfirmedCount()
                                ->orderBy('nombre')
                                ->get()
                                ->mapWithKeys(fn (Group $group): array => [
                                    $group->id => sprintf(
                                        '%s (%d cupos disponibles)',
                                        $group->nombre,
                                        max(0, $group->cupo_maximo - ($group->confirmed_count ?? 0)),
                                    ),
                                ])
                                ->toArray())
                            ->required(),
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo de la solicitud')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Preinscription $record, array $data, StudentRequestService $service): void {
                        try {
                            $service->submit(
                                $record,
                                StudentRequestType::CAMBIO_GRUPO,
                                (int) $data['group_id_destino'],
                                $data['motivo'],
                            );
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo registrar la solicitud.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Solicitud enviada. Recibirás la respuesta por correo.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('solicitarDevolucion')
                    ->label('Solicitar devolución')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Preinscription $record): bool => $record->status === PreinscriptionStatus::INSCRITO
                        && ($record->pending_devolucion_count ?? 0) === 0)
                    ->modalHeading('Solicitar devolución')
                    ->modalDescription('La coordinación del curso revisará tu solicitud de devolución. El reembolso no es inmediato.')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo de la solicitud')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Preinscription $record, array $data, StudentRequestService $service): void {
                        try {
                            $service->submit(
                                $record,
                                StudentRequestType::DEVOLUCION,
                                null,
                                $data['motivo'],
                            );
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo registrar la solicitud.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Solicitud enviada. Recibirás la respuesta por correo.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentPreinscriptions::route('/'),
            'view' => Pages\ViewStudentPreinscription::route('/{record}'),
        ];
    }
}
