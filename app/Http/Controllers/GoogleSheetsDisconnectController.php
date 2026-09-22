<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

class GoogleSheetsDisconnectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        SystemSetting::set('google_sheets_access_token', '');
        SystemSetting::set('google_sheets_refresh_token', '');
        SystemSetting::set('google_sheets_auth_method', 'service_account');

        Notification::make()
            ->title('Cuenta Google desconectada')
            ->success()
            ->send();

        return redirect('/admin/google-sheets-settings');
    }
}
