<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Models\SystemSetting;
use App\Services\GoogleSheetsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GoogleSheetsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Configuración de Google Sheets';

    protected static string $view = 'filament.pages.settings.google-sheets-settings';

    public ?array $data = [];

    public ?string $oauthUserEmail = null;

    public bool $isOAuthConnected = false;

    public string $oauthRedirectUrl = '';

    public function mount(): void
    {
        $this->isOAuthConnected = GoogleSheetsService::isOAuthConnected();
        $this->oauthRedirectUrl = GoogleSheetsService::getOAuthRedirectUrl();

        $this->oauthUserEmail = $this->isOAuthConnected
            ? app(GoogleSheetsService::class)->getOAuthUserEmail()
            : null;

        $this->form->fill([
            'spreadsheet_id' => SystemSetting::get('google_sheets_spreadsheet_id', ''),
            'credentials_json' => SystemSetting::get('google_sheets_credentials_json', ''),
            'enabled' => SystemSetting::get('google_sheets_enabled', '1') === '1',
            'auth_method' => GoogleSheetsService::getAuthMethod(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('auth_method')
                    ->label('Método de autenticación')
                    ->options([
                        'service_account' => 'Service Account (recomendado para servidores)',
                        'oauth' => 'Cuenta Google personal',
                    ])
                    ->default('service_account')
                    ->live()
                    ->required(),
                Forms\Components\Textarea::make('credentials_json')
                    ->label('Service Account JSON')
                    ->placeholder('Pegue el contenido del archivo JSON de credenciales')
                    ->rows(8)
                    ->visible(fn (Forms\Get $get) => $get('auth_method') === 'service_account'),
                Forms\Components\TextInput::make('spreadsheet_id')
                    ->label('Spreadsheet ID')
                    ->placeholder('Deje vacío para crear automáticamente')
                    ->maxLength(255),
                Forms\Components\Toggle::make('enabled')
                    ->label('Habilitar exportación')
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if ($data['auth_method'] === 'service_account' && ! empty($data['credentials_json'])) {
            $path = storage_path('app/google/credentials.json');
            $dir = dirname($path);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, $data['credentials_json']);
        }

        SystemSetting::set('google_sheets_auth_method', $data['auth_method']);
        SystemSetting::set('google_sheets_spreadsheet_id', $data['spreadsheet_id'] ?? '');
        SystemSetting::set('google_sheets_credentials_json', $data['credentials_json'] ?? '');
        SystemSetting::set('google_sheets_enabled', $data['enabled'] ? '1' : '0');

        $canCreate = ($data['auth_method'] === 'service_account')
            || ($data['auth_method'] === 'oauth' && GoogleSheetsService::isOAuthConnected());

        if ($canCreate && empty($data['spreadsheet_id'])) {
            try {
                $service = app(GoogleSheetsService::class);
                $id = $service->createSpreadsheet('UMSS Cursos - Planillas');
                $service->setSpreadsheetId($id);
                $service->createTemplateTabs();
                $service->populateTemplateTabs();

                $data['spreadsheet_id'] = $id;
                SystemSetting::set('google_sheets_spreadsheet_id', $id);

                Notification::make()
                    ->title('Spreadsheet creado')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return;
            }
        }

        $this->form->fill($data);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}
