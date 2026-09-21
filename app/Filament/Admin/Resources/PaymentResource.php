<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $modelLabelPlural = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('preinscription_id')
                    ->relationship('preinscription', 'nombres')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('monto')
                    ->numeric()
                    ->prefix('Bs.')
                    ->required()
                    ->rules(function ($get) {
                        return function ($attribute, $value, $fail) use ($get) {
                            $preinscriptionId = $get('preinscription_id');
                            if ($preinscriptionId && $value) {
                                try {
                                    app(PaymentService::class)->verifyAmount((int) $preinscriptionId, (float) $value);
                                } catch (ValidationException $e) {
                                    $fail($e->getMessage());
                                }
                            }
                        };
                    }),
                Forms\Components\Select::make('metodo')
                    ->options(PaymentMethod::class)
                    ->required(),
                Forms\Components\Select::make('estado')
                    ->options(PaymentStatus::class)
                    ->default(PaymentStatus::PENDIENTE)
                    ->required(),
                Forms\Components\TextInput::make('numero_comprobante')
                    ->maxLength(100),
                Forms\Components\Select::make('verificado_por')
                    ->relationship('verifier', 'name')
                    ->searchable()
                    ->nullable()
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                Forms\Components\DateTimePicker::make('verificado_en')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('preinscription.full_name')
                    ->label('Participante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('preinscription.cod_sis')
                    ->label('Cód. SIS')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\IconColumn::make('preinscription.fotocopia_ci')
                    ->label('Fotocopia CI')
                    ->boolean(),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('Bs.'),
                Tables\Columns\TextColumn::make('metodo')
                    ->label('Método')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('numero_comprobante')
                    ->label('N° Comprobante'),
                Tables\Columns\TextColumn::make('verifier.name')
                    ->label('Verificado por'),
                Tables\Columns\TextColumn::make('verificado_en')
                    ->label('Fecha de verificación')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de pago')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(PaymentStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => $record->estado === PaymentStatus::PENDIENTE)
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar recepción del pago?')
                    ->modalDescription('Se validará el pago inmediatamente y el estudiante quedará inscrito con notificación por correo.')
                    ->action(function (Payment $record, PaymentService $service) {
                        $service->verify($record);

                        Notification::make()
                            ->title('Pago verificado y estudiante inscrito correctamente.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $record) => $record->estado === PaymentStatus::PENDIENTE)
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar pago')
                    ->modalSubHeading('El pago no fue recibido o presento problemas')
                    ->form([
                        Forms\Components\TextInput::make('motivo_rechazo')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Payment $record, array $data, PaymentService $service) {
                        $service->reject($record, $data['motivo_rechazo']);

                        Notification::make()
                            ->title('Pago rechazado correctamente.')
                            ->warning()
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
