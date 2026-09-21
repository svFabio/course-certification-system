<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Filament\Admin\Resources\PreinscriptionResource\Pages;
use App\Models\Payment;
use App\Models\Preinscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PreinscriptionResource extends Resource
{
    protected static ?string $model = Preinscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Inscripciones';

    protected static ?int $navigationSort = 1;

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
                    ->required()
                    ->maxLength(20),
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
                    ->options(PreinscriptionStatus::class)
                    ->default(PreinscriptionStatus::PENDIENTE_PAGO),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ci')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno'),
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
                //
            ])
            ->actions([
                Tables\Actions\Action::make('registrarPago')
                    ->label('Validar Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Preinscription $record) => $record->status === PreinscriptionStatus::PENDIENTE_PAGO)
                    ->requiresConfirmation()
                    ->modalHeading('Registrar y Validar Pago en Caja')
                    ->modalDescription(fn (Preinscription $record) => "¿Confirmar cobro de Bs. {$record->price} a {$record->full_name} e inscribirlo automáticamente?")
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
                    ])
                    ->action(function (Preinscription $record, array $data) {
                        Payment::create([
                            'preinscription_id' => $record->id,
                            'monto' => $record->price,
                            'metodo' => $data['metodo'],
                            'numero_comprobante' => $data['numero_comprobante'] ?? null,
                            'estado' => PaymentStatus::VERIFICADO,
                            'verificado_por' => auth()->id(),
                            'verificado_en' => now(),
                        ]);

                        Notification::make()
                            ->title('Pago registrado y estudiante inscrito con éxito.')
                            ->success()
                            ->send();
                    }),
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
