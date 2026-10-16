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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Caja & Tesorería';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Auditoría de Pagos';

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

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
                    ->rules(function (Forms\Get $get) {
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
                Forms\Components\TextInput::make('numero_comprobante')
                    ->regex('/^[a-zA-Z0-9\-_]+$/')
                    ->helperText('Solo caracteres alfanuméricos, guiones o guiones bajos')
                    ->maxLength(50),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de pago')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('preinscription.full_name')
                    ->label('Participante')
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno', 'ci']),
                Tables\Columns\TextColumn::make('preinscription.ci')
                    ->label('CI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('preinscription.group.course.nombre')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('preinscription.group.nombre')
                    ->label('Grupo'),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('Bs. ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('metodo')
                    ->label('Método')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('numero_comprobante')
                    ->label('N° Comprobante')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('verifier.name')
                    ->label('Verificado por')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('verificado_en')
                    ->label('Fecha verificación')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(PaymentStatus::class),
                Tables\Filters\SelectFilter::make('metodo')
                    ->label('Método')
                    ->options(PaymentMethod::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'preinscription.group.course',
                'verifier',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
