<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Inscripciones';

    protected static ?int $navigationSort = 2;

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
                                $preinscription = Preinscription::with('group.course')->find($preinscriptionId);
                                if ($preinscription) {
                                    $expected = BusinessRules::calculatePrice(
                                        (int) $preinscription->group->course->carga_horaria,
                                        $preinscription->tipo_participante
                                    );
                                    if ((float) $value !== $expected) {
                                        $fail("El monto correcto para este participante es Bs. {$expected}");
                                    }
                                }
                            }
                        };
                    }),
                Forms\Components\Select::make('metodo')
                    ->options(PaymentMethod::class)
                    ->required(),
                Forms\Components\Select::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'verificado' => 'Verificado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->default('pendiente')
                    ->required(),
                Forms\Components\TextInput::make('numero_comprobante')
                    ->maxLength(100),
                Forms\Components\Select::make('verificado_por')
                    ->relationship('verifier', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('verificado_en'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('preinscription.nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('monto')
                    ->numeric()
                    ->prefix('Bs.'),
                Tables\Columns\TextColumn::make('metodo')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge(fn (string $state) => match ($state) {
                        'pendiente' => 'warning',
                        'verificado' => 'success',
                        'rechazado' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('numero_comprobante'),
                Tables\Columns\TextColumn::make('verificado_por'),
                Tables\Columns\TextColumn::make('verificado_en')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'verificado' => 'Verificado',
                        'rechazado' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => $record->verificado_por === null && $record->estado !== 'rechazado')
                    ->requiresConfirmation()
                    ->modalHeading('Verificar pago')
                    ->modalSubdescription('Confirma que el pago fue recibido en caja facultativa')
                    ->form([
                        Forms\Components\DateTimePicker::make('verificado_en')
                            ->label('Fecha de verificacion')
                            ->default(now()),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'verificado_por' => auth()->id(),
                            'verificado_en' => $data['verificado_en'],
                            'estado' => 'verificado',
                        ]);
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $record) => $record->estado !== 'rechazado')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar pago')
                    ->modalSubdescription('El pago no fue recibido o presento problemas')
                    ->form([
                        Forms\Components\TextInput::make('motivo_rechazo')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'estado' => 'rechazado',
                            'motivo_rechazo' => $data['motivo_rechazo'],
                        ]);
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
