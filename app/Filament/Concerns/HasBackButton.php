<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Js;

trait HasBackButton
{
    protected function getBackAction(): Action
    {
        $fallbackUrl = property_exists($this, 'previousUrl') && filled($this->previousUrl)
            ? $this->previousUrl
            : static::getResource()::getUrl('index');

        return Action::make('back')
            ->label('Volver')
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from($fallbackUrl).')')
            ->url($fallbackUrl);
    }
}
