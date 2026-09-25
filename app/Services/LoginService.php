<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\RoleHome;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_SECONDS = 900;

    public function attempt(string $email, string $password, bool $remember = false): User
    {
        $email = strtolower(trim($email));
        $key = $this->throttleKey($email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos fallidos. Intente de nuevo en '.ceil($seconds / 60).' minutos.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function redirectUrl(User $user): string
    {
        return RoleHome::url($user);
    }

    public function throttleKey(string $email): string
    {
        return 'login:'.strtolower($email).'|'.request()->ip();
    }
}
