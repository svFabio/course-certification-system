<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\SpreadsheetProperties;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
    private ?Sheets $service = null;

    private string $spreadsheetId;

    private ?Spreadsheet $spreadsheetCache = null;

    public function __construct()
    {
        $this->spreadsheetId = SystemSetting::get('google_sheets_spreadsheet_id', '');
    }

    public function duplicateTemplateTab(string $templateTabName, string $newTabName): void
    {
        $sheetId = $this->getSheetIdByName($templateTabName);

        $requestBody = new BatchUpdateSpreadsheetRequest;
        $requestBody->setRequests([
            new Request([
                'copySheet' => [
                    'source' => [
                        'sheetId' => $sheetId,
                    ],
                    'destination' => [
                        'title' => $newTabName,
                    ],
                ],
            ]),
        ]);

        $this->service()->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);

        $this->forgetSpreadsheetCache();
    }

    public function writeCells(string $range, array $values): void
    {
        $body = new ValueRange([
            'values' => $values,
        ]);

        $this->service()->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function checkTabExists(string $tabName): bool
    {
        foreach ($this->fetchSpreadsheet()->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return true;
            }
        }

        return false;
    }

    public function getSheetIdByName(string $tabName): int
    {
        foreach ($this->fetchSpreadsheet()->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return $sheet->getProperties()->getSheetId();
            }
        }

        throw new \InvalidArgumentException("Sheet tab '{$tabName}' not found in spreadsheet.");
    }

    public function readCells(string $range): array
    {
        $response = $this->service()->spreadsheets_values->get($this->spreadsheetId, $range);

        return $response->getValues() ?? [];
    }

    public function createSpreadsheet(string $title): string
    {
        $spreadsheet = new Spreadsheet([
            'properties' => new SpreadsheetProperties([
                'title' => $title,
            ]),
        ]);

        $created = $this->service()->spreadsheets->create($spreadsheet);

        return $created->getSpreadsheetId();
    }

    public function createTemplateTabs(): void
    {
        $requests = [
            new Request([
                'addSheet' => [
                    'properties' => [
                        'title' => 'PLANTILLA_CAJA',
                    ],
                ],
            ]),
            new Request([
                'addSheet' => [
                    'properties' => [
                        'title' => 'PLANTILLA_DOCENTE',
                    ],
                ],
            ]),
        ];

        $requestBody = new BatchUpdateSpreadsheetRequest;
        $requestBody->setRequests($requests);

        $this->service()->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);

        $this->forgetSpreadsheetCache();
    }

    public function populateTemplateTabs(): void
    {
        $templateBuilder = app(TemplateSheetBuilder::class);

        $this->writeCells('PLANTILLA_CAJA!A1', $templateBuilder->buildCashTemplate());
        $this->writeCells('PLANTILLA_DOCENTE!A1', $templateBuilder->buildTeacherTemplate());
    }

    public function createTab(string $tabName): void
    {
        $requestBody = new BatchUpdateSpreadsheetRequest;
        $requestBody->setRequests([
            new Request([
                'addSheet' => [
                    'properties' => [
                        'title' => $tabName,
                    ],
                ],
            ]),
        ]);

        $this->service()->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);

        $this->forgetSpreadsheetCache();
    }

    public function deleteTab(string $tabName): void
    {
        $sheetId = $this->getSheetIdByName($tabName);

        $requestBody = new BatchUpdateSpreadsheetRequest;
        $requestBody->setRequests([
            new Request([
                'deleteSheet' => [
                    'sheetId' => $sheetId,
                ],
            ]),
        ]);

        $this->service()->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);

        $this->forgetSpreadsheetCache();
    }

    /**
     * Delete default blank sheets ('Sheet1', 'Hoja 1') and unused template sheets
     * ('PLANTILLA_CAJA', 'PLANTILLA_DOCENTE') when real content sheets exist.
     * Google requires at least 1 sheet to remain in the spreadsheet at all times.
     */
    public function cleanupDefaultTabs(array $protectedTabNames = []): void
    {
        $sheets = $this->fetchSpreadsheet()->getSheets();
        $totalSheets = count($sheets);

        $junkTitles = ['Sheet1', 'Hoja 1', 'PLANTILLA_CAJA', 'PLANTILLA_DOCENTE'];
        $deleteRequests = [];

        foreach ($sheets as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            $sheetId = $sheet->getProperties()->getSheetId();

            $isJunk = in_array($title, $junkTitles, true)
                || ($sheetId === 0 && (str_starts_with($title, 'Sheet') || str_starts_with($title, 'Hoja')));

            if ($isJunk && ! in_array($title, $protectedTabNames, true)) {
                if ($totalSheets - count($deleteRequests) > 1) {
                    $deleteRequests[] = new Request([
                        'deleteSheet' => [
                            'sheetId' => $sheetId,
                        ],
                    ]);
                }
            }
        }

        if (! empty($deleteRequests)) {
            $requestBody = new BatchUpdateSpreadsheetRequest;
            $requestBody->setRequests($deleteRequests);
            $this->service()->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);
            $this->forgetSpreadsheetCache();
        }
    }

    public function setSpreadsheetId(string $spreadsheetId): void
    {
        $this->spreadsheetId = $spreadsheetId;

        $this->forgetSpreadsheetCache();
    }

    private function service(): Sheets
    {
        return $this->service ??= $this->buildService();
    }

    /**
     * Fetch the tab list once per instance instead of downloading the whole
     * spreadsheet for every tab lookup (Sheets quota is read-limited).
     */
    private function fetchSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheetCache ??= $this->service()->spreadsheets->get($this->spreadsheetId);
    }

    private function forgetSpreadsheetCache(): void
    {
        $this->spreadsheetCache = null;
    }

    public static function getAuthMethod(): string
    {
        return SystemSetting::get('google_sheets_auth_method', 'service_account');
    }

    public static function isOAuthConnected(): bool
    {
        $refreshToken = SystemSetting::get('google_sheets_refresh_token', '');

        return ! empty($refreshToken) && self::getAuthMethod() === 'oauth';
    }

    public static function isOAuthEnvConfigured(): bool
    {
        return filled(config('services.google.sheets.oauth.client_id'))
            && filled(config('services.google.sheets.oauth.client_secret'));
    }

    public static function getOAuthRedirectUrl(): string
    {
        if (! self::isOAuthEnvConfigured()) {
            return '';
        }

        $client = new Client;
        $client->setClientId(config('services.google.sheets.oauth.client_id'));
        $client->setClientSecret(config('services.google.sheets.oauth.client_secret'));
        $client->setRedirectUri(config('services.google.sheets.oauth.redirect_uri'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client->createAuthUrl();
    }

    /**
     * Lightweight connectivity check against the configured spreadsheet.
     *
     * @return array{ok: bool, message: string, spreadsheet_url?: string}
     */
    public function testConnection(): array
    {
        if (! self::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Google Sheets aún no está configurado. Guarde credenciales, habilite la exportación y asegúrese de tener un Spreadsheet ID.',
            ];
        }

        try {
            $spreadsheet = $this->service()->spreadsheets->get($this->spreadsheetId);
            $title = $spreadsheet->getProperties()?->getTitle() ?? 'Sin título';
            $url = 'https://docs.google.com/spreadsheets/d/'.$this->spreadsheetId;

            return [
                'ok' => true,
                'message' => "Conexión correcta. Spreadsheet: «{$title}».",
                'spreadsheet_url' => $url,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No se pudo conectar: '.$e->getMessage(),
            ];
        }
    }

    public function getOAuthUserEmail(): ?string
    {
        if (! self::isOAuthConnected()) {
            return null;
        }

        try {
            $accessToken = json_decode(SystemSetting::get('google_sheets_access_token', '{}'), true);
            $client = $this->service()->getClient();
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken(SystemSetting::get('google_sheets_refresh_token'));
                SystemSetting::set('google_sheets_access_token', json_encode($client->getAccessToken()));
            }

            $token = $client->getAccessToken();
            $httpClient = new \GuzzleHttp\Client;
            $response = $httpClient->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                ],
            ]);

            $user = json_decode($response->getBody()->getContents(), true);

            return $user['email'] ?? null;
        } catch (\Exception) {
            return null;
        }
    }

    private function buildService(): Sheets
    {
        $client = new Client;
        $client->setApplicationName(config('services.google.sheets.app_name', 'UMSS Cursos'));

        $authMethod = self::getAuthMethod();

        if ($authMethod === 'oauth' && self::isOAuthConnected()) {
            $client->setClientId(config('services.google.sheets.oauth.client_id'));
            $client->setClientSecret(config('services.google.sheets.oauth.client_secret'));
            $client->setRedirectUri(config('services.google.sheets.oauth.redirect_uri'));

            $accessToken = json_decode(SystemSetting::get('google_sheets_access_token', '{}'), true);
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken(SystemSetting::get('google_sheets_refresh_token'));
                SystemSetting::set('google_sheets_access_token', json_encode($client->getAccessToken()));
            }
        } else {
            $client->setAuthConfig($this->getCredentialsPath());
        }

        $client->addScope(Sheets::SPREADSHEETS);
        $client->setAccessType('offline');

        return new Sheets($client);
    }

    private function getCredentialsPath(): string
    {
        $path = self::resolveCredentialsPath();

        if (! file_exists($path)) {
            $json = SystemSetting::get('google_sheets_credentials_json', '');
            if (filled($json)) {
                $dir = dirname($path);
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @file_put_contents($path, $json);
                @chmod($path, 0600);

                if (file_exists($path)) {
                    return $path;
                }
            }

            throw new \RuntimeException(
                'Google Service Account credentials not found. '
                .'Configure them in Admin > Configuración > Google Sheets.'
            );
        }

        return $path;
    }

    public static function resolveCredentialsPath(): string
    {
        $path = (string) config('services.google.sheets.credentials_path', storage_path('app/google/credentials.json'));

        if ($path === '') {
            return storage_path('app/google/credentials.json');
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    public static function isConfigured(): bool
    {
        $spreadsheetId = SystemSetting::get('google_sheets_spreadsheet_id', '');
        $enabled = SystemSetting::get('google_sheets_enabled', '1') === '1';
        $authMethod = self::getAuthMethod();

        if ($authMethod === 'oauth') {
            return $enabled
                && ! empty($spreadsheetId)
                && self::isOAuthConnected();
        }

        $hasCredentials = file_exists(self::resolveCredentialsPath())
            || filled(SystemSetting::get('google_sheets_credentials_json', ''));

        return $enabled
            && ! empty($spreadsheetId)
            && $hasCredentials;
    }
}
