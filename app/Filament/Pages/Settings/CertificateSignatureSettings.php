<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CertificateSignatureSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 110;

    protected static ?string $navigationLabel = 'Firmas de Certificados';

    protected static ?string $title = 'Firmas Institucionales de Certificados';

    protected static ?string $slug = 'certificate-signature-settings';

    protected static string $view = 'filament.pages.settings.certificate-signature-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'director_academico_nombre' => SystemSetting::get('director_academico_nombre', ''),
            'director_academico_cargo' => SystemSetting::get('director_academico_cargo', 'Director Académico'),
            'director_academico_firma_path' => SystemSetting::get('director_academico_firma_path', null),
            'jefe_departamento_nombre' => SystemSetting::get('jefe_departamento_nombre', ''),
            'jefe_departamento_cargo' => SystemSetting::get('jefe_departamento_cargo', 'Jefe de Departamento'),
            'jefe_departamento_firma_path' => SystemSetting::get('jefe_departamento_firma_path', null),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Director Académico')
                    ->description('Datos y estampa de firma del Director Académico de Formación Continua.')
                    ->schema([
                        Forms\Components\TextInput::make('director_academico_nombre')
                            ->label('Nombre Completo y Grado Académico')
                            ->placeholder('Nombre y grado académico del director')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('director_academico_cargo')
                            ->label('Cargo Institucional')
                            ->default('Director Académico')
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('director_academico_firma_path')
                            ->label('Firma Digital (PNG con fondo transparente)')
                            ->disk('cloudinary')
                            ->directory('firmas/autoridades')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(2048)
                            ->helperText('Formato PNG transparente, tamaño recomendado 600x300 px.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Jefe de Departamento')
                    ->description('Datos y estampa de firma del Jefe de Departamento de Formación Continua.')
                    ->schema([
                        Forms\Components\TextInput::make('jefe_departamento_nombre')
                            ->label('Nombre Completo y Grado Académico')
                            ->placeholder('Nombre y grado académico del jefe de departamento')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('jefe_departamento_cargo')
                            ->label('Cargo Institucional')
                            ->default('Jefe de Departamento')
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('jefe_departamento_firma_path')
                            ->label('Firma Digital (PNG con fondo transparente)')
                            ->disk('cloudinary')
                            ->directory('firmas/autoridades')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(2048)
                            ->helperText('Formato PNG transparente, tamaño recomendado 600x300 px.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SystemSetting::set('director_academico_nombre', $state['director_academico_nombre'] ?? '');
        SystemSetting::set('director_academico_cargo', $state['director_academico_cargo'] ?? 'Director Académico');
        SystemSetting::set('director_academico_firma_path', $state['director_academico_firma_path'] ?? null);

        SystemSetting::set('jefe_departamento_nombre', $state['jefe_departamento_nombre'] ?? '');
        SystemSetting::set('jefe_departamento_cargo', $state['jefe_departamento_cargo'] ?? 'Jefe de Departamento');
        SystemSetting::set('jefe_departamento_firma_path', $state['jefe_departamento_firma_path'] ?? null);

        Notification::make()
            ->title('Configuración de firmas guardada correctamente')
            ->success()
            ->send();
    }
}
