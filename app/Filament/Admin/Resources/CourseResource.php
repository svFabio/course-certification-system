<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\TipoParticipante;
use App\Exports\CourseClosingExport;
use App\Exports\FinancialReportExport;
use App\Filament\Admin\Resources\CourseResource\Pages;
use App\Jobs\BroadcastCourseToTelegram;
use App\Models\Course;
use App\Services\CourseService;
use App\Services\TelegramChannelService;
use App\Support\BusinessRules;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Curso';

    protected static ?string $pluralModelLabel = 'Cursos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos Generales')
                        ->schema([
                            Forms\Components\TextInput::make('nombre')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            Forms\Components\Textarea::make('contenido')
                                ->rows(4),
                            Forms\Components\FileUpload::make('portada_path')
                                ->label('Imagen de portada')
                                ->image()
                                ->disk('cloudinary')
                                ->directory('courses')
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1200')
                                ->imageResizeTargetHeight('675')
                                ->maxSize(5120)
                                ->helperText('Imagen de cabecera para el catálogo público (máx. 5MB).'),
                            Forms\Components\Select::make('carga_horaria')
                                ->options(array_combine(
                                    BusinessRules::VALID_HOURS,
                                    array_map(fn ($h) => "{$h} horas", BusinessRules::VALID_HOURS),
                                ))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if (! $state) {
                                        return;
                                    }
                                    $hours = (int) $state;
                                    $set('precio_umss', BusinessRules::calculatePrice($hours, TipoParticipante::UMSS));
                                    $set('precio_externo', BusinessRules::calculatePrice($hours, TipoParticipante::EXTERNO));
                                    $set('precio_auxiliar', BusinessRules::calculatePrice($hours, TipoParticipante::AUXILIAR));
                                }),
                            Forms\Components\Select::make('nivel')
                                ->options(CourseLevel::class)
                                ->required(),
                            Forms\Components\Select::make('periodo')
                                ->options(function (): array {
                                    $year = now()->year;

                                    return [
                                        "I-{$year}" => "I-{$year}",
                                        "II-{$year}" => "II-{$year}",
                                        'Invierno' => 'Invierno',
                                        'Verano' => 'Verano',
                                    ];
                                })
                                ->required(),
                            Forms\Components\Select::make('instructor_id')
                                ->relationship('instructor', 'name')
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options(CourseStatus::class)
                                ->default(CourseStatus::EN_PREPARACION)
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make('Precios')
                        ->description('Se precargan según la carga horaria; ajusta el monto vigente (inflación, tarifas).')
                        ->schema([
                            Forms\Components\TextInput::make('precio_umss')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required()
                                ->minValue(0)
                                ->helperText('Precio para participantes UMSS.'),
                            Forms\Components\TextInput::make('precio_externo')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required()
                                ->minValue(0)
                                ->helperText('Precio para participantes externos.'),
                            Forms\Components\TextInput::make('precio_auxiliar')
                                ->numeric()
                                ->prefix('Bs.')
                                ->required()
                                ->minValue(0)
                                ->helperText('Precio auxiliar con 50% de descuento.'),
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
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('Docente'),
                Tables\Columns\TextColumn::make('precio_umss')
                    ->label('Precio UMSS')
                    ->numeric()
                    ->prefix('Bs.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('campañaRedes')
                        ->label('Campaña en Redes')
                        ->icon('heroicon-o-megaphone')
                        ->color('info')
                        ->visible(fn (Course $record): bool => $record->status === CourseStatus::PUBLICADO)
                        ->modalHeading(fn (Course $record): string => "Campaña de Difusión: {$record->nombre}")
                        ->modalDescription('Herramientas para promocionar este curso en Facebook, WhatsApp y canales institucionales.')
                        ->form(function (Course $record): array {
                            $group = $record->nextGroupOpenForPreinscription();
                            $url = $group ? route('preinscripcion', $group) : route('home');
                            $linkLabel = $group ? 'Preinscripciones abiertas' : 'Ver cursos disponibles';
                            $promoText = "🎓 CURSO DISPONIBLE EN LA UMSS\n\n"
                                ."📌 {$record->nombre}\n\n"
                                .'📖 '.strip_tags((string) $record->contenido)."\n\n"
                                ."⏱ Carga horaria: {$record->carga_horaria} horas\n"
                                .'📊 Nivel: '.($record->nivel?->value ?? 'General')."\n"
                                ."💰 Inversión:\n"
                                .'   • Estudiantes UMSS: Bs. '.number_format((float) $record->precio_umss, 2)."\n"
                                .'   • Profesionales y Externos: Bs. '.number_format((float) $record->precio_externo, 2)."\n\n"
                                ."🔗 {$linkLabel}:\n{$url}";

                            $fbUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url);
                            $waUrl = 'https://api.whatsapp.com/send?text='.rawurlencode($promoText);

                            return [
                                Forms\Components\Textarea::make('copy_redes')
                                    ->label('Texto para publicaciones')
                                    ->default($promoText)
                                    ->rows(8),
                                Forms\Components\View::make('filament.forms.components.campana-redes-links')
                                    ->viewData([
                                        'fbUrl' => $fbUrl,
                                        'waUrl' => $waUrl,
                                    ]),
                            ];
                        })
                        ->modalSubmitActionLabel('Publicar en Canal de Telegram')
                        ->action(function (Course $record, TelegramChannelService $service): void {
                            if (! $service->isConfigured()) {
                                Notification::make()
                                    ->title('Aviso de difusión')
                                    ->body('El bot de Telegram o el ID del canal no están configurados en el entorno (TELEGRAM_BOT_TOKEN / TELEGRAM_CHANNEL_CHAT_ID).')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            BroadcastCourseToTelegram::dispatch($record->id);

                            Notification::make()
                                ->title('Difusión en cola')
                                ->body('El anuncio se publicará en el canal de Telegram en segundo plano.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('publicar')
                        ->label('Publicar curso')
                        ->icon('heroicon-o-globe-alt')
                        ->color('success')
                        ->visible(fn (Course $record): bool => $record->status === CourseStatus::EN_PREPARACION)
                        ->requiresConfirmation()
                        ->modalHeading('Publicar curso')
                        ->modalDescription('¿Está seguro de publicar este curso? Quedará visible en el catálogo público para preinscripciones.')
                        ->action(function (Course $record, CourseService $service): void {
                            $service->publish($record);
                            Notification::make()
                                ->title('Curso publicado con éxito')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('despublicar')
                        ->label('Despublicar')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn (Course $record): bool => $record->status === CourseStatus::PUBLICADO)
                        ->requiresConfirmation()
                        ->modalHeading('Retirar publicación')
                        ->modalDescription('El curso volverá al estado "En preparación" y ya no aceptará nuevas preinscripciones públicas.')
                        ->action(function (Course $record, CourseService $service): void {
                            $service->unpublish($record);
                            Notification::make()
                                ->title('Curso retirado de publicación')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('reporteJefatura')
                        ->label('Acta de Cierre (Excel)')
                        ->icon('heroicon-o-document-chart-bar')
                        ->color('info')
                        ->action(fn (Course $record) => Excel::download(
                            new CourseClosingExport($record),
                            'cierre-curso-'.str($record->nombre)->slug().'.xlsx'
                        )),
                    Tables\Actions\Action::make('reporteFinanciero')
                        ->label('Balance Caja (Excel)')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->action(fn (Course $record) => Excel::download(
                            new FinancialReportExport($record),
                            'financiero-curso-'.str($record->nombre)->slug().'.xlsx'
                        )),
                    Tables\Actions\Action::make('cancelar')
                        ->label('Cancelar curso')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Course $record, CourseService $service): bool => $service->canCancel($record))
                        ->form([
                            Forms\Components\Textarea::make('motivo_cancelacion')
                                ->label('Motivo de cancelación')
                                ->required()
                                ->maxLength(1000)
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Curso')
                        ->modalDescription('Esta acción marcará el curso como cancelado. Debe registrar el motivo técnico o administrativo.')
                        ->action(function (Course $record, array $data, CourseService $service): void {
                            $service->cancel($record, (string) $data['motivo_cancelacion']);
                            Notification::make()
                                ->title('Curso cancelado correctamente')
                                ->danger()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->hidden(fn (Course $record): bool => in_array($record->status, [CourseStatus::FINALIZADO, CourseStatus::EN_CURSO], true)),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Opciones del curso'),
            ])
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
                Infolists\Components\TextEntry::make('motivo_cancelacion')
                    ->label('Motivo de cancelación')
                    ->visible(fn (Course $record): bool => filled($record->motivo_cancelacion)),
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
            CourseResource\RelationManagers\CertificatesRelationManager::class,
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
