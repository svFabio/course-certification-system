<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\LoginService;
use App\Support\RoleHome;
use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
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

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Correo o contraseña incorrectos',
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        try {
            $user = app(LoginService::class)->attempt(
                $email,
                $password,
                (bool) ($data['remember'] ?? false),
            );
        } catch (ValidationException $exception) {
            $message = $exception->validator->errors()->first() ?: 'Correo o contraseña incorrectos';

            if (str_contains($message, 'Demasiados intentos')) {
                Notification::make()
                    ->title($message)
                    ->danger()
                    ->send();

                return null;
            }

            throw ValidationException::withMessages([
                'data.email' => $message,
            ]);
        }

        return new class($user) implements LoginResponse
        {
            public function __construct(private readonly \App\Models\User $user) {}

            public function toResponse($request)
            {
                return redirect()->to(RoleHome::url($this->user));
            }
        };
    }
}
