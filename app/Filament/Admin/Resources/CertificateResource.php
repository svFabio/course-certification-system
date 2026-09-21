<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Filament\Admin\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Evaluación & Certificados';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $modelLabelPlural = 'Certificados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('preinscription_id')
                    ->relationship('preinscription', 'nombres')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('tipo')
                    ->options(CertificateType::class)
                    ->required(),
                Forms\Components\TextInput::make('codigo_unico')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->maxLength(50),
                Forms\Components\TextInput::make('pdf_path')
                    ->maxLength(500),
                Forms\Components\Select::make('signature_status')
                    ->options(SignatureStatus::class)
                    ->default(SignatureStatus::PENDIENTE)
                    ->required(),
                Forms\Components\DateTimePicker::make('emitido_en'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('preinscription.full_name')
                    ->label('Participante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo de certificado')
                    ->badge(),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código único')
                    ->searchable(),
                Tables\Columns\TextColumn::make('signature_status')
                    ->label('Estado de firma')
                    ->badge(),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->label('Fecha de emisión')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'view' => Pages\ViewCertificate::route('/{record}'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }
}
