<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Support\BusinessRules;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Curso';

    protected static ?string $modelLabelPlural = 'Cursos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos Generales')
                        ->schema([
                            Forms\Components\TextInput::make('nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('contenido')
                                ->rows(4),
                            Forms\Components\Select::make('carga_horaria')
                                ->options(array_combine(
                                    BusinessRules::VALID_HOURS,
                                    array_map(fn ($h) => "{$h} horas", BusinessRules::VALID_HOURS),
                                ))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($set, $state) {
                                    $prices = BusinessRules::PRICING[$state] ?? [];
                                    $set('precio_umss', $prices['umss'] ?? 0);
                                    $set('precio_externo', $prices['externo'] ?? 0);
                                    $set('precio_auxiliar', $prices['auxiliar'] ?? 0);
                                }),
                            Forms\Components\TextInput::make('nivel'),
                            Forms\Components\TextInput::make('periodo'),
                            Forms\Components\Select::make('instructor_id')
                                ->relationship('instructor', 'name')
                                ->searchable()
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make('Precios')
                        ->schema([
                            Forms\Components\TextInput::make('precio_umss')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required(),
                            Forms\Components\TextInput::make('precio_externo')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required(),
                            Forms\Components\TextInput::make('precio_auxiliar')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carga_horaria')
                    ->badge(),
                Tables\Columns\TextColumn::make('nivel'),
                Tables\Columns\TextColumn::make('periodo'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('fecha_inicio_preinscripcion')
                    ->date('d/m/Y')
                    ->label('Inicio preinsc.')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fecha_fin_preinscripcion')
                    ->date('d/m/Y')
                    ->label('Fin preinsc.')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('instructor.name'),
                Tables\Columns\TextColumn::make('precio_umss')
                    ->numeric()
                    ->prefix('Bs.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CourseStatus::class),
                Tables\Filters\SelectFilter::make('carga_horaria')
                    ->options(array_combine(
                        BusinessRules::VALID_HOURS,
                        array_map(fn ($h) => "{$h}h", BusinessRules::VALID_HOURS),
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->recordUrl(fn (Course $record): string => CourseResource::getUrl('view', ['record' => $record]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('nombre'),
                Infolists\Components\TextEntry::make('contenido'),
                Infolists\Components\TextEntry::make('carga_horaria')
                    ->suffix(' horas'),
                Infolists\Components\TextEntry::make('nivel'),
                Infolists\Components\TextEntry::make('periodo'),
                Infolists\Components\TextEntry::make('status')
                    ->badge(),
                Infolists\Components\TextEntry::make('fecha_inicio_preinscripcion')
                    ->date('d/m/Y')
                    ->label('Inicio de preinscripción')
                    ->placeholder('No definido'),
                Infolists\Components\TextEntry::make('fecha_fin_preinscripcion')
                    ->date('d/m/Y')
                    ->label('Fin de preinscripción')
                    ->placeholder('No definido'),
                Infolists\Components\TextEntry::make('instructor.name'),
                Infolists\Components\TextEntry::make('precio_umss')
                    ->numeric()
                    ->prefix('Bs.'),
                Infolists\Components\TextEntry::make('precio_externo')
                    ->numeric()
                    ->prefix('Bs.'),
                Infolists\Components\TextEntry::make('precio_auxiliar')
                    ->numeric()
                    ->prefix('Bs.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CourseResource\RelationManagers\GroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
