<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\RelationManagers;

use App\Enums\CertificateLifecycleStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Certificados emitidos';

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $pluralModelLabel = 'Certificados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('participante')
                    ->label('Beneficiario')
                    ->content(fn (?Certificate $record): string => $record?->beneficiaryName() ?? '—'),
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
                Forms\Components\Placeholder::make('estado_label')
                    ->label('Estado')
                    ->content(fn (?Certificate $record): string => $record?->estado?->getLabel() ?? '—'),
                Forms\Components\Placeholder::make('emitido')
                    ->label('Fecha de emisión')
                    ->content(fn (?Certificate $record): string => $record?->emitido_en?->format('d/m/Y H:i') ?? '—'),
                Forms\Components\Placeholder::make('entregado')
                    ->label('Fecha de entrega física')
                    ->content(fn (?Certificate $record): string => $record?->entregado_en?->format('d/m/Y H:i') ?? '—'),
                Forms\Components\Placeholder::make('escaneado')
                    ->label('Fecha de digitalización')
                    ->content(fn (?Certificate $record): string => $record?->escaneado_en?->format('d/m/Y H:i') ?? '—'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['preinscription.group', 'instructor']))
            ->recordTitleAttribute('codigo_unico')
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
                Tables\Columns\TextColumn::make('grupo')
                    ->label('Grupo')
                    ->state(fn (Certificate $record): string => $record->isInstructorCertificate()
                        ? ($record->grupos_detalle ?: 'Todos los grupos')
                        : ($record->preinscription?->group?->nombre ?? '—'))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código único')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grupo')
                    ->label('Grupo')
                    ->options(fn (): array => $this->getOwnerRecord()->groups()->pluck('nombre', 'id')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('preinscription', fn (Builder $q) => $q->where('group_id', $data['value']))
                        : $query
                    ),
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(CertificateType::class),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(CertificateLifecycleStatus::class),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('subirEscaneo')
                        ->label(fn (Certificate $record): string => $record->hasScannedCopy() ? 'Reemplazar foto/digital' : 'Subir foto/escaneo')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->visible(fn (Certificate $record): bool => ! $record->isInstructorCertificate())
                        ->form([
                            Forms\Components\FileUpload::make('archivo')
                                ->label('Fotografía o escaneo del certificado firmado')
                                ->disk('cloudinary')
                                ->directory('certificates/scanned')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(10240)
                                ->required()
                                ->helperText('Foto o documento en PDF, JPG, PNG o WebP. Máximo 10 MB.'),
                        ])
                        ->modalHeading(fn (Certificate $record): string => $record->hasScannedCopy()
                            ? 'Reemplazar certificado firmado'
                            : 'Subir foto o escaneo de certificado firmado')
                        ->modalDescription('Suba la fotografía o escaneo del certificado físico con las firmas correspondientes para su verificación pública.')
                        ->modalSubmitActionLabel('Guardar')
                        ->action(function (Certificate $record, array $data, CertificateService $service): void {
                            try {
                                $service->attachScannedCopy($record, $data['archivo']);
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo guardar el archivo')
                                    ->body(collect($e->errors())->flatten()->implode(' '))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Certificado digitalizado con éxito. Ya es accesible en la verificación pública.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('descargar')
                        ->label('Descargar imprimible')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Certificate $record, CertificateService $service): string => $service->publicDownloadUrl($record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('descargarDigital')
                        ->label('Descargar digital')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (Certificate $record): bool => $record->hasScannedCopy())
                        ->action(function (Certificate $record, CertificateService $service): ?Response {
                            try {
                                return $service->downloadScanned($record);
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo descargar')
                                    ->body(collect($e->errors())->flatten()->implode(' '))
                                    ->danger()
                                    ->send();

                                return null;
                            }
                        }),
                    Tables\Actions\Action::make('marcarEntregado')
                        ->label('Marcar entregado')
                        ->icon('heroicon-o-hand-raised')
                        ->color('warning')
                        ->visible(fn (Certificate $record): bool => ! $record->isInstructorCertificate()
                            && $record->estado === CertificateLifecycleStatus::GENERADO)
                        ->requiresConfirmation()
                        ->modalHeading('Marcar entrega física')
                        ->modalDescription('Confirma que el certificado impreso y firmado fue entregado al estudiante.')
                        ->action(function (Certificate $record, CertificateService $service): void {
                            try {
                                $service->markAsDelivered($record);
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo marcar la entrega')
                                    ->body(collect($e->errors())->flatten()->implode(' '))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Certificado marcado como entregado')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('reenviarNotificacion')
                        ->label('Reenviar notificación')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->visible(fn (): bool => filled(config('mail.mailers.'.config('mail.default').'.username')))
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
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Acciones del certificado'),
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }
}
