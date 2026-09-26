<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Filament\Admin\Resources\PreinscriptionResource\Pages;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class PreinscriptionResource extends Resource
{
    protected static ?string $model = Preinscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Preinscripción';

    protected static ?string $modelLabelPlural = 'Preinscripciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('ci')
                    ->label('Cédula de Identidad (CI)')
                    ->required()
                    ->regex('/^[0-9]{4,10}(-[0-9A-Z]{1,2})?$/i')
                    ->helperText('4 a 10 dígitos numéricos (ej. 7894561 o 7894561-1A)')
                    ->maxLength(20),
                Forms\Components\TextInput::make('cod_sis')
                    ->label('Código SIS')
                    ->regex('/^[0-9]{7,10}$/')
                    ->helperText('7 a 10 dígitos numéricos')
                    ->maxLength(50),
                Forms\Components\TextInput::make('nombres')
                    ->required()
                    ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/')
                    ->maxLength(100),
                Forms\Components\TextInput::make('apellido_paterno')
                    ->required()
                    ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/')
                    ->maxLength(100),
                Forms\Components\TextInput::make('apellido_materno')
                    ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/')
                    ->maxLength(100),
                Forms\Components\TextInput::make('celular')
                    ->tel()
                    ->regex('/^[67][0-9]{7}$/')
                    ->helperText('8 dígitos iniciando en 6 o 7')
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(150),
                Forms\Components\Select::make('tipo_participante')
                    ->options(TipoParticipante::class)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(PreinscriptionStatus::class)
                    ->default(PreinscriptionStatus::PENDIENTE_PAGO),
                Forms\Components\Toggle::make('fotocopia_ci')
                    ->label('Fotocopia de C.I. entregada')
                    ->default(false),
                Forms\Components\FileUpload::make('auxiliar_certificado_path')
                    ->label('Certificado de auxiliar practicante (Jefatura)')
                    ->disk('cloudinary')
                    ->directory('auxiliar-certificados')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->helperText('Emitido por Jefatura tras concluir el semestre. Solo aplica descuento del 50% tras su aprobación.')
                    ->visible(fn (Get $get): bool => $get('tipo_participante') === TipoParticipante::AUXILIAR->value),
                Forms\Components\Select::make('auxiliar_certificado_aprobado')
                    ->label('Aprobación del certificado auxiliar')
                    ->options([
                        null => 'Sin evaluar',
                        true => 'Aprobado',
                        false => 'Rechazado',
                    ])
                    ->visible(fn (Get $get): bool => $get('tipo_participante') === TipoParticipante::AUXILIAR->value),
                Forms\Components\Textarea::make('auxiliar_certificado_motivo')
                    ->label('Motivo')
                    ->helperText('Obligatorio al rechazar el certificado auxiliar.')
                    ->visible(fn (Get $get): bool => $get('tipo_participante') === TipoParticipante::AUXILIAR->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ci')
                    ->label('CI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cod_sis')
                    ->label('Cód. SIS')
                    ->getStateUsing(fn ($record) => match (true) {
                        empty($record->cod_sis) => 'EXTERNO',
                        strlen($record->cod_sis) !== 9 => "⚠ {$record->cod_sis}",
                        default => $record->cod_sis,
                    })
                    ->color(fn ($record) => match (true) {
                        empty($record->cod_sis) => 'gray',
                        strlen($record->cod_sis) !== 9 => 'warning',
                        default => null,
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->label('Nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno')
                    ->label('Ap. Paterno'),
                Tables\Columns\TextColumn::make('apellido_materno')
                    ->label('Ap. Materno')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('celular')
                    ->label('Celular')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('fotocopia_ci')
                    ->label('Fotocopia CI')
                    ->boolean(),
                Tables\Columns\TextColumn::make('auxiliar_certificado_aprobado')
                    ->label('Certif. auxiliar')
                    ->badge()
                    ->formatStateUsing(function ($record): ?string {
                        if ($record->tipo_participante !== TipoParticipante::AUXILIAR->value) {
                            return null;
                        }

                        return match ($record->auxiliar_certificado_aprobado) {
                            true => 'Aprobado',
                            false => 'Rechazado',
                            default => 'Pendiente',
                        };
                    })
                    ->color(function ($record): ?string {
                        if ($record->tipo_participante !== TipoParticipante::AUXILIAR->value) {
                            return null;
                        }

                        return match ($record->auxiliar_certificado_aprobado) {
                            true => 'success',
                            false => 'danger',
                            default => 'warning',
                        };
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.nombre')
                    ->label('Grupo'),
                Tables\Columns\TextColumn::make('tipo_participante')
                    ->label('Tipo participante')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_id')
                    ->relationship('group', 'nombre')
                    ->label('Grupo')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(PreinscriptionStatus::class)
                    ->default(PreinscriptionStatus::INSCRITO->value),
            ])
            ->defaultSort('apellido_paterno')
            ->actions([
                Tables\Actions\Action::make('aprobarCertificadoAuxiliar')
                    ->label('Aprobar certificado auxiliar')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (Preinscription $record): bool => $record->tipo_participante === TipoParticipante::AUXILIAR->value
                        && $record->auxiliar_certificado_aprobado !== true
                        && $record->auxiliar_certificado_path !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar certificado de auxiliar practicante')
                    ->modalDescription('Al aprobarlo, el participante obtendrá el 50% de descuento de auxiliar practicante.')
                    ->action(function (Preinscription $record): void {
                        $record->update([
                            'auxiliar_certificado_aprobado' => true,
                            'auxiliar_certificado_aprobado_por' => Auth::id(),
                            'auxiliar_certificado_aprobado_en' => now(),
                            'auxiliar_certificado_motivo' => null,
                        ]);

                        Notification::make()
                            ->title('Certificado aprobado. El participante obtiene el 50% de descuento.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rechazarCertificadoAuxiliar')
                    ->label('Rechazar certificado auxiliar')
                    ->icon('heroicon-o-document-x-mark')
                    ->color('danger')
                    ->visible(fn (Preinscription $record): bool => $record->tipo_participante === TipoParticipante::AUXILIAR->value
                        && $record->auxiliar_certificado_aprobado !== true
                        && $record->auxiliar_certificado_path !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar certificado de auxiliar practicante')
                    ->modalDescription('El participante no tendrá descuento y deberá pagar tarifa de la comunidad UMSS.')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Preinscription $record, array $data): void {
                        $record->update([
                            'auxiliar_certificado_aprobado' => false,
                            'auxiliar_certificado_aprobado_por' => Auth::id(),
                            'auxiliar_certificado_aprobado_en' => now(),
                            'auxiliar_certificado_motivo' => $data['motivo'],
                        ]);

                        Notification::make()
                            ->title('Certificado rechazado. Se aplicará tarifa de comunidad UMSS.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('registrarPago')
                    ->label('Validar Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Preinscription $record) => $record->status === PreinscriptionStatus::PENDIENTE_PAGO)
                    ->requiresConfirmation()
                    ->modalHeading('Registrar y Validar Pago en Caja')
                    ->modalDescription(fn (Preinscription $record) => "¿Confirmar cobro de Bs. {$record->chargeable_price} a {$record->full_name} e inscribirlo automáticamente?")
                    ->form([
                        Forms\Components\Select::make('metodo')
                            ->label('Método de pago')
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::EFECTIVO)
                            ->required(),
                        Forms\Components\TextInput::make('numero_comprobante')
                            ->label('Número de comprobante / recibo')
                            ->placeholder('Opcional')
                            ->maxLength(100),
                        Forms\Components\Toggle::make('fotocopia_ci')
                            ->label('Fotocopia de C.I. entregada físicamente')
                            ->default(fn (Preinscription $record) => (bool) $record->fotocopia_ci),
                    ])
                    ->action(function (Preinscription $record, array $data) {
                        app(PaymentService::class)->registerAndVerify(
                            $record,
                            $data['metodo'],
                            $data['numero_comprobante'] ?? null,
                            (bool) ($data['fotocopia_ci'] ?? false),
                        );

                        Notification::make()
                            ->title('Pago registrado y estudiante inscrito con éxito.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cambiarGrupo')
                    ->label('Cambiar de grupo')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (Preinscription $record) => in_array($record->status, [
                        PreinscriptionStatus::PENDIENTE_PAGO,
                        PreinscriptionStatus::INSCRITO,
                    ], true))
                    ->form([
                        Forms\Components\Select::make('nuevo_grupo_id')
                            ->label('Grupo de destino')
                            ->options(fn (Preinscription $record) => Group::where('course_id', $record->group->course_id)
                                ->whereNot('id', $record->group_id)
                                ->get()
                                ->mapWithKeys(fn (Group $group) => [
                                    $group->id => "{$group->nombre} ({$group->hora_inicio->format('H:i')} - {$group->hora_fin->format('H:i')})",
                                ]))
                            ->required(),
                    ])
                    ->action(function (Preinscription $record, array $data, PreinscriptionService $service) {
                        $destination = Group::findOrFail((int) $data['nuevo_grupo_id']);

                        try {
                            $service->moveToGroup($record, $destination);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo realizar el cambio de grupo.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Participante reubicado al grupo de destino.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('solicitarDevolucion')
                    ->label('Solicitar devolución')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Preinscription $record) => $record->status === PreinscriptionStatus::INSCRITO)
                    ->requiresConfirmation()
                    ->modalHeading('Solicitar devolución / reembolso')
                    ->modalDescription('Se marcará al participante como "devolución pendiente" y su pago quedará en espera de confirmación de reembolso.')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo de la devolución')
                            ->helperText('Ej.: el grupo no alcanzó el cupo mínimo o el participante no aceptó el cambio de grupo.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Preinscription $record, array $data, PaymentService $service) {
                        try {
                            $service->requestRefund($record, $data['motivo']);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo solicitar la devolución.')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Devolución solicitada. Confirme el reembolso cuando corresponda.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('confirmarReembolso')
                    ->label('Confirmar reembolso')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Preinscription $record) => $record->status === PreinscriptionStatus::DEVOLUCION_PENDIENTE)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar reembolso del pago')
                    ->modalDescription('El pago verificado quedará marcado como reembolsado y el participante liberará su cupo.')
                    ->action(function (Preinscription $record, PaymentService $service) {
                        $service->confirmRefund($record);

                        Notification::make()
                            ->title('Reembolso confirmado.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('descargarBoleta')
                    ->label('Boleta de inscripción')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->visible(fn (Preinscription $record) => $record->status === PreinscriptionStatus::INSCRITO)
                    ->url(fn (Preinscription $record) => URL::signedRoute('boleta.descargar', ['preinscription' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPreinscriptions::route('/'),
            'create' => Pages\CreatePreinscription::route('/create'),
            'view' => Pages\ViewPreinscription::route('/{record}'),
            'edit' => Pages\EditPreinscription::route('/{record}/edit'),
        ];
    }
}
