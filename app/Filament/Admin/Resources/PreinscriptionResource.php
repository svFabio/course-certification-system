<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Filament\Admin\Resources\PreinscriptionResource\Pages;
use App\Models\Preinscription;
use App\Services\PaymentService;
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
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->label('Nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno')
                    ->label('Ap. Paterno'),
                Tables\Columns\TextColumn::make('celular')
                    ->label('Celular')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('fotocopia_ci')
                    ->label('Fotocopia CI')
                    ->boolean(),
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
