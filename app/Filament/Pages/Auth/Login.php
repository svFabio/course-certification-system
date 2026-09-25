<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
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

        if (
            ! $user->hasRole(UserRole::ADMIN->value)
            && ! $user->hasRole(UserRole::INSTRUCTOR->value)
            && ! $user->hasRole(UserRole::STUDENT->value)
        ) {
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

                if ($user->hasRole(UserRole::ADMIN->value)) {
                    return redirect()->to('/admin');
                }

                if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
                    return redirect()->to('/instructor');
                }

                if ($user->hasRole(UserRole::STUDENT->value)) {
                    return redirect()->to('/estudiante');
                }

                return redirect()->to('/');
            }
        };
    }
}

