<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CertificateType;
use App\Filament\Admin\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Evaluación & Certificados';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $pluralModelLabel = 'Certificados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('participante')
                    ->label('Beneficiario')
                    ->content(fn (?Certificate $record): string => $record?->beneficiaryName() ?? '—'),
                Forms\Components\Placeholder::make('curso')
                    ->label('Curso')
                    ->content(fn (?Certificate $record): string => $record?->course?->nombre ?? '—'),
                Forms\Components\Placeholder::make('tipo_label')
                    ->label('Tipo de certificado')
                    ->content(fn (?Certificate $record): string => $record?->tipo?->getLabel() ?? '—'),
                Forms\Components\Placeholder::make('grupos')
                    ->label('Grupo(s)')
                    ->content(fn (?Certificate $record): string => $record?->grupos_detalle ?: '—')
                    ->visible(fn (?Certificate $record): bool => $record?->isInstructorCertificate() ?? false),
                Forms\Components\Placeholder::make('codigo')
                    ->label('Código único')
                    ->content(fn (?Certificate $record): string => $record?->codigo_unico ?? '—'),
                Forms\Components\Placeholder::make('emitido')
                    ->label('Fecha de emisión')
                    ->content(fn (?Certificate $record): string => $record?->emitido_en?->format('d/m/Y H:i') ?? '—'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('beneficiario')
                    ->label('Beneficiario')
                    ->state(fn (Certificate $record): string => $record->beneficiaryName())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $outer) use ($search): void {
                            $outer->whereHas('preinscription', function (Builder $q) use ($search): void {
                                $q->where('nombres', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_materno', 'ilike', "%{$search}%")
                                    ->orWhere('ci', 'ilike', "%{$search}%");
                            })->orWhereHas('instructor', function (Builder $q) use ($search): void {
                                $q->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            });
                        });
                    }),
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('grupo')
                    ->label('Grupo')
                    ->state(fn (Certificate $record): string => $record->isInstructorCertificate()
                        ? ($record->grupos_detalle ?: 'Todos los grupos')
                        : ($record->preinscription?->group?->nombre ?? '—'))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código único')
                    ->searchable()
                    ->copyable()
                    ->url(fn (Certificate $record): string => route('certificado.descargar', $record))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultGroup('course_id')
            ->groups([
                TableGroup::make('course_id')
                    ->label('Curso')
                    ->getTitleFromRecordUsing(fn (Certificate $record): string => $record->course?->nombre ?? 'Sin curso')
                    ->collapsible(),
                TableGroup::make('tipo')
                    ->label('Tipo de certificado')
                    ->getTitleFromRecordUsing(fn (Certificate $record): string => $record->tipo?->getLabel() ?? 'Sin tipo')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('grupo')
                    ->label('Grupo')
                    ->options(fn (): array => Group::query()
                        ->with('course')
                        ->orderBy('course_id')
                        ->get()
                        ->mapWithKeys(fn (Group $g) => [$g->id => "{$g->course->nombre} — {$g->nombre}"])
                        ->toArray()
                    )
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('preinscription', fn (Builder $q) => $q->where('group_id', $data['value']))
                        : $query
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(CertificateType::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['preinscription.group', 'instructor', 'course']);
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
