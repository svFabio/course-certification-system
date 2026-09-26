<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Auth\RedirectByRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly RedirectByRole $redirectByRole) {}

    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole->redirect(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        $user = Auth::user();

        if (! $this->redirectByRole->hasAccess($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Su cuenta no tiene permisos de acceso al sistema.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectByRole->redirect($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
