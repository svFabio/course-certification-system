<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Filament\Admin\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Evaluación & Certificados';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $pluralModelLabel = 'Certificados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('participante')
                    ->label('Beneficiario')
                    ->content(fn (?Certificate $record): string => $record?->beneficiaryName() ?? '—'),
                Forms\Components\Placeholder::make('curso')
                    ->label('Curso')
                    ->content(fn (?Certificate $record): string => $record?->course?->nombre ?? '—'),
                Forms\Components\Placeholder::make('tipo_label')
                    ->label('Tipo de certificado')
                    ->content(fn (?Certificate $record): string => $record?->tipo?->getLabel() ?? '—'),
                Forms\Components\Placeholder::make('grupos')
                    ->label('Grupo(s)')
                    ->content(fn (?Certificate $record): string => $record?->grupos_detalle ?: '—')
                    ->visible(fn (?Certificate $record): bool => $record?->isInstructorCertificate() ?? false),
                Forms\Components\Placeholder::make('codigo')
                    ->label('Código único')
                    ->content(fn (?Certificate $record): string => $record?->codigo_unico ?? '—'),
                Forms\Components\Placeholder::make('signature_status_label')
                    ->label('Estado de firma')
                    ->content(fn (?Certificate $record): string => $record?->signature_status?->getLabel() ?? '—')
                    ->helperText('Para avanzar el flujo de firmas use la acción «Avanzar firma» en la tabla o ficha del certificado.'),
                Forms\Components\Placeholder::make('emitido')
                    ->label('Fecha de emisión')
                    ->content(fn (?Certificate $record): string => $record?->emitido_en?->format('d/m/Y H:i') ?? '—'),
                Forms\Components\Placeholder::make('demora')
                    ->label('Demora administrativa')
                    ->content(fn (?Certificate $record): string => $record?->administrativeDelayLabel() ?? '—')
                    ->helperText('Tiempo desde la generación del certificado hasta que quedó listo (o hasta ahora si sigue en proceso).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('beneficiario')
                    ->label('Beneficiario')
                    ->state(fn (Certificate $record): string => $record->beneficiaryName())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $outer) use ($search): void {
                            $outer->whereHas('preinscription', function (Builder $q) use ($search): void {
                                $q->where('nombres', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_materno', 'ilike', "%{$search}%")
                                    ->orWhere('ci', 'ilike', "%{$search}%");
                            })->orWhereHas('instructor', function (Builder $q) use ($search): void {
                                $q->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            });
                        });
                    }),
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código único')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('signature_status')
                    ->label('Estado de firma')
                    ->badge(),
                Tables\Columns\TextColumn::make('demora')
                    ->label('Demora')
                    ->state(fn (Certificate $record): string => $record->administrativeDelayLabel())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('listo_en')
                    ->label('Listo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('signature_status')
                    ->label('Estado de firma')
                    ->options(SignatureStatus::class),
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(CertificateType::class),
            ])
            ->actions([
                Tables\Actions\Action::make('avanzarFirma')
                    ->label(fn (Certificate $record): string => $record->signature_status->advanceLabel()
                        ? 'Registrar: '.$record->signature_status->advanceLabel()
                        : 'Flujo completo')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (Certificate $record): bool => $record->signature_status->next() !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Avanzar firma')
                    ->modalDescription(fn (Certificate $record): string => 'Se actualizará el estado a «'
                        .($record->signature_status->advanceLabel() ?? '').'».')
                    ->action(function (Certificate $record, CertificateService $service): void {
                        try {
                            $updated = $service->advanceSignature($record);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo avanzar la firma')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Firma actualizada')
                            ->body($updated->signature_status->getLabel())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('aprobarDirectamente')
                    ->label('Aprobar certificado')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Certificate $record): bool => $record->signature_status !== SignatureStatus::LISTO)
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar certificado directamente')
                    ->modalDescription('Se marcará el certificado como listo (omitiendo los pasos intermedios), se generará el PDF y se notificará al beneficiario.')
                    ->action(function (Certificate $record, CertificateService $service): void {
                        try {
                            $service->updateSignatureStatus($record, SignatureStatus::LISTO);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo aprobar el certificado')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Certificado aprobado')
                            ->body('El PDF fue generado y el beneficiario fue notificado.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reenviarNotificacion')
                    ->label('Reenviar notificación')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (Certificate $record): bool => $record->signature_status === SignatureStatus::LISTO
                        && filled(config('mail.mailers.'.config('mail.default').'.username')))
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar notificación')
                    ->modalDescription('Se enviará nuevamente el correo de disponibilidad del certificado.')
                    ->action(function (Certificate $record, CertificateService $service): void {
                        try {
                            $service->resendReadyNotification($record);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo reenviar')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Notificación reenviada')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['preinscription', 'instructor', 'course']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
