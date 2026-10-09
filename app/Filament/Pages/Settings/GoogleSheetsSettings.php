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

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Google Sheets';

    protected static ?string $title = 'Configuración de Google Sheets';

    protected static ?string $slug = 'google-sheets-settings';

    protected static string $view = 'filament.pages.settings.google-sheets-settings';

    public ?array $data = [];

    public ?string $oauthUserEmail = null;

    public bool $isOAuthConnected = false;

    public bool $isOAuthEnvConfigured = false;

    public bool $isSheetsConfigured = false;

    public string $oauthRedirectUrl = '';

    public string $oauthCallbackUri = '';

    public function mount(): void
    {
        $this->refreshStatus();

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
                Forms\Components\Section::make('Autenticación')
                    ->description('Elija cómo la plataforma se conectará a Google Sheets para exportar planillas de caja y docente.')
                    ->schema([
                        Forms\Components\Radio::make('auth_method')
                            ->label('Método de autenticación')
                            ->options([
                                'service_account' => 'Service Account (recomendado para servidores)',
                                'oauth' => 'Cuenta Google personal (OAuth)',
                            ])
                            ->default('service_account')
                            ->live()
                            ->required(),
                        Forms\Components\Textarea::make('credentials_json')
                            ->label('JSON de Service Account')
                            ->helperText('Pegue aquí el contenido completo del archivo JSON descargado desde Google Cloud Console.')
                            ->placeholder('{ "type": "service_account", "project_id": "...", ... }')
                            ->rows(10)
                            ->visible(fn (Forms\Get $get): bool => $get('auth_method') === 'service_account'),
                    ]),
                Forms\Components\Section::make('Spreadsheet')
                    ->schema([
                        Forms\Components\TextInput::make('spreadsheet_id')
                            ->label('Spreadsheet ID')
                            ->helperText('Deje vacío y pulse Guardar para crear uno nuevo automáticamente (requiere credenciales válidas).')
                            ->placeholder('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('enabled')
                            ->label('Habilitar exportación a Google Sheets')
                            ->helperText('Si está desactivado, los botones de exportar en Grupos no enviarán datos.')
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if ($data['auth_method'] === 'service_account' && filled($data['credentials_json'] ?? null)) {
            $decoded = json_decode((string) $data['credentials_json'], true);

            if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'service_account') {
                Notification::make()
                    ->title('JSON inválido')
                    ->body('El contenido debe ser un JSON de Service Account válido (campo "type": "service_account").')
                    ->danger()
                    ->send();

                return;
            }

            $path = storage_path('app/google/credentials.json');
            $dir = dirname($path);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        SystemSetting::set('google_sheets_auth_method', $data['auth_method']);
        SystemSetting::set('google_sheets_spreadsheet_id', $data['spreadsheet_id'] ?? '');
        SystemSetting::set('google_sheets_credentials_json', $data['credentials_json'] ?? '');
        SystemSetting::set('google_sheets_enabled', ! empty($data['enabled']) ? '1' : '0');

        $canCreate = ($data['auth_method'] === 'service_account' && filled($data['credentials_json'] ?? null))
            || ($data['auth_method'] === 'oauth' && GoogleSheetsService::isOAuthConnected());

        if ($canCreate && blank($data['spreadsheet_id'] ?? null)) {
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
                    ->body('Se creó el libro «UMSS Cursos - Planillas» en Google Sheets.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('No se pudo crear el spreadsheet')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                $this->refreshStatus();

                return;
            }
        }

        $this->form->fill($data);
        $this->refreshStatus();

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        try {
            $result = app(GoogleSheetsService::class)->testConnection();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error al probar la conexión')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($result['ok']) {
            Notification::make()
                ->title('Conexión exitosa')
                ->body($result['message'])
                ->success()
                ->actions([
                    ...(! empty($result['spreadsheet_url']) ? [
                        \Filament\Notifications\Actions\Action::make('abrir')
                            ->label('Abrir spreadsheet')
                            ->url($result['spreadsheet_url'], shouldOpenInNewTab: true),
                    ] : []),
                ])
                ->send();

            return;
        }

        Notification::make()
            ->title('Sin conexión')
            ->body($result['message'])
            ->warning()
            ->send();
    }

    private function refreshStatus(): void
    {
        $this->isOAuthEnvConfigured = GoogleSheetsService::isOAuthEnvConfigured();
        $this->isOAuthConnected = GoogleSheetsService::isOAuthConnected();
        $this->isSheetsConfigured = GoogleSheetsService::isConfigured();
        $this->oauthRedirectUrl = GoogleSheetsService::getOAuthRedirectUrl();
        $this->oauthCallbackUri = (string) config('services.google.sheets.oauth.redirect_uri');
        $this->oauthUserEmail = $this->isOAuthConnected
            ? app(GoogleSheetsService::class)->getOAuthUserEmail()
            : null;
    }
}
