<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Models\SystemSetting;

trait ConfiguresSheets
{
    /**
     * @var non-empty-string|null
     */
    private ?string $sheetsCredentialsTempPath = null;

    private function configureSheets(): void
    {
        SystemSetting::set('google_sheets_spreadsheet_id', 'test-spreadsheet-id');
        SystemSetting::set('google_sheets_enabled', '1');
        SystemSetting::set('google_sheets_auth_method', 'service_account');

        $path = tempnam(sys_get_temp_dir(), 'gscreds').'.json';
        file_put_contents($path, json_encode(['type' => 'service_account'], JSON_THROW_ON_ERROR));
        config(['services.google.sheets.credentials_path' => $path]);

        $this->sheetsCredentialsTempPath = $path;

        if (method_exists($this, 'beforeApplicationDestroyed')) {
            $this->beforeApplicationDestroyed(function () use ($path): void {
                @unlink($path);
            });
        }
    }

    protected function tearDownConfiguresSheets(): void
    {
        if ($this->sheetsCredentialsTempPath !== null) {
            @unlink($this->sheetsCredentialsTempPath);
            $this->sheetsCredentialsTempPath = null;
        }
    }
}
