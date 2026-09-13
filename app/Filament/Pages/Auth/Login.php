<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function getHeading(): string
    {
        return 'Acceso Institucional';
    }

    public function getSubheading(): ?string
    {
        return 'Ingrese con sus credenciales de la Universidad Mayor de San Simón.';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Iniciar Sesión');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = auth()->user();

        if (! $user->hasRole('admin') && ! $user->hasRole('instructor')) {
            auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Su cuenta no tiene permisos de acceso al sistema de gestión.',
            ]);
        }

        session()->regenerate();

        return new class implements LoginResponse
        {
            public function toResponse($request)
            {
                $user = auth()->user();

                if ($user->hasRole('admin')) {
                    return redirect()->to('/admin');
                }

                if ($user->hasRole('instructor')) {
                    return redirect()->to('/instructor');
                }

                return redirect()->to('/');
            }
        };
    }
}
