<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GroupResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Models\Preinscription;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class PreinscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'preinscriptions';

    protected static ?string $title = 'Preinscripciones';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ci')
                ->label('Cédula de Identidad (CI)')
                ->required()
                ->maxLength(20)
                ->rules([
                    fn (?Preinscription $record, RelationManager $livewire): Unique => Rule::unique('preinscriptions', 'ci')
                        ->where('group_id', $livewire->getOwnerRecord()->id)
                        ->ignore($record?->id),
                ])
                ->validationMessages([
                    'unique' => 'Este participante (CI) ya se encuentra registrado en este grupo.',
                ]),
            Forms\Components\TextInput::make('nombres')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('apellido_paterno')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('apellido_materno')
                ->maxLength(255),
            Forms\Components\TextInput::make('celular')
                ->tel()
                ->maxLength(20),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('tipo_participante')
                ->options(TipoParticipante::class)
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(PreinscriptionStatus::class)
                ->default(PreinscriptionStatus::PENDIENTE_PAGO)
                ->disabled()
                ->dehydrated(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ci')
                    ->label('CI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Participante'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_participante')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data): Preinscription {
                        return app(PreinscriptionService::class)->register(
                            [...$data, 'group_id' => $this->getOwnerRecord()->id],
                            enforceEnabledGroup: false,
                        );
                    }),
            ])
            ->actions([
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
                Tables\Actions\Action::make('marcarRetirado')
                    ->label('Marcar retirado')
                    ->icon('heroicon-o-user-minus')
                    ->color('gray')
                    ->visible(fn (Preinscription $record) => in_array($record->status, [
                        PreinscriptionStatus::PENDIENTE_PAGO,
                        PreinscriptionStatus::INSCRITO,
                    ], true))
                    ->requiresConfirmation()
                    ->modalHeading('Marcar al participante como retirado')
                    ->modalDescription('El participante liberará su cupo en el grupo. Su registro se conserva con estado "retirado".')
                    ->action(function (Preinscription $record, PreinscriptionService $service): void {
                        $service->markAsWithdrawn($record);

                        Notification::make()
                            ->title('Participante marcado como retirado.')
                            ->status('gray')
                            ->send();
                    }),
            ]);
    }
}
