<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\CertificateResource\Pages;
use App\Filament\Admin\Resources\CourseResource\RelationManagers\GroupsRelationManager;
use App\Models\Course;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificateResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $slug = 'certificados';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Evaluación & Certificados';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Certificados';

    protected static ?string $modelLabel = 'Certificados del Curso';

    protected static ?string $pluralModelLabel = 'Certificados';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Course $record): string => CertificateResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('Docente')
                    ->placeholder('Sin asignar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('carga_horaria')
                    ->label('Carga horaria')
                    ->badge()
                    ->suffix(' hrs'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('groups_count')
                    ->label('Grupos')
                    ->counts('groups')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('certificates_count')
                    ->label('Certificados')
                    ->counts('certificates')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(CourseStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('verGrupos')
                    ->label('Ver Grupos')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->url(fn (Course $record): string => CertificateResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['instructor'])
            ->withCount(['groups', 'certificates']);
    }

    public static function getRelations(): array
    {
        return [
            GroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
