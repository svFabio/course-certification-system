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
    private Sheets $service;

    private string $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = SystemSetting::get('google_sheets_spreadsheet_id', '');
        $this->service = $this->buildService();
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

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);
    }

    public function writeCells(string $range, array $values): void
    {
        $body = new ValueRange([
            'values' => $values,
        ]);

        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function checkTabExists(string $tabName): bool
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);

        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return true;
            }
        }

        return false;
    }

    public function getSheetIdByName(string $tabName): int
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);

        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return $sheet->getProperties()->getSheetId();
            }
        }

        throw new \InvalidArgumentException("Sheet tab '{$tabName}' not found in spreadsheet.");
    }

    public function readCells(string $range): array
    {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);

        return $response->getValues() ?? [];
    }

    public function createSpreadsheet(string $title): string
    {
        $spreadsheet = new Spreadsheet([
            'properties' => new SpreadsheetProperties([
                'title' => $title,
            ]),
        ]);

        $created = $this->service->spreadsheets->create($spreadsheet);

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

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);
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

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);
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

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $requestBody);
    }

    public function setSpreadsheetId(string $spreadsheetId): void
    {
        $this->spreadsheetId = $spreadsheetId;
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

    public static function getOAuthRedirectUrl(): string
    {
        $client = new Client;
        $client->setClientId(config('services.google.sheets.oauth.client_id'));
        $client->setClientSecret(config('services.google.sheets.oauth.client_secret'));
        $client->setRedirectUri(config('services.google.sheets.oauth.redirect_uri'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client->createAuthUrl();
    }

    public function getOAuthUserEmail(): ?string
    {
        if (! self::isOAuthConnected()) {
            return null;
        }

        try {
            $accessToken = json_decode(SystemSetting::get('google_sheets_access_token', '{}'), true);
            $client = $this->service->getClient();
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
        $path = storage_path('app/google/credentials.json');

        if (! file_exists($path)) {
            throw new \RuntimeException(
                'Google Service Account credentials not found. '
                .'Configure them in Admin > Configuración > Google Sheets.'
            );
        }

        return $path;
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

        $credentialsPath = storage_path('app/google/credentials.json');

        return $enabled
            && ! empty($spreadsheetId)
            && file_exists($credentialsPath);
    }
}
