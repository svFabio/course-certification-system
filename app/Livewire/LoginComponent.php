<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\LoginService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Iniciar sesión')]
class LoginComponent extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect(app(LoginService::class)->redirectUrl(auth()->user()), navigate: false);
        }
    }

    public function submit(LoginService $login): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        try {
            $user = $login->attempt($this->email, $this->password, $this->remember);
        } catch (ValidationException $exception) {
            $this->addError('email', $exception->validator->errors()->first() ?: 'Correo o contraseña incorrectos');

            return;
        }

        $this->redirect($login->redirectUrl($user), navigate: false);
    }

    public function render()
    {
        return view('livewire.login-component');
    }
}
