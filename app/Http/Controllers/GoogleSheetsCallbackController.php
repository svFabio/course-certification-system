<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleSheetsCallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $code = $request->input('code');

        if (! $code) {
            Notification::make()
                ->title('Error de autenticación')
                ->body('No se recibió el código de autorización de Google.')
                ->danger()
                ->send();

            return redirect('/admin/google-sheets-settings');
        }

        try {
            $client = $this->buildOAuthClient();
            $client->fetchAccessTokenWithAuthCode($code);

            $accessToken = $client->getAccessToken();
            $refreshToken = $client->getRefreshToken();

            SystemSetting::set('google_sheets_access_token', json_encode($accessToken));
            SystemSetting::set('google_sheets_refresh_token', $refreshToken);
            SystemSetting::set('google_sheets_auth_method', 'oauth');

            Notification::make()
                ->title('Cuenta Google conectada exitosamente')
                ->body('Ahora puede crear spreadsheets en su Google Drive personal.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al conectar con Google')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return redirect('/admin/google-sheets-settings');
    }

    private function buildOAuthClient(): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.sheets.oauth.client_id'));
        $client->setClientSecret(config('services.google.sheets.oauth.client_secret'));
        $client->setRedirectUri(config('services.google.sheets.oauth.redirect_uri'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
