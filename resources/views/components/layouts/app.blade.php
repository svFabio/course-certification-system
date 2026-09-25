<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'UMSS Cursos') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-umss-gray-100 text-umss-black font-sans min-h-screen flex flex-col antialiased">
    <nav class="bg-white border-b border-umss-gray-100 border-t-4 border-t-umss-navy sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <a href="/" class="text-xl font-semibold text-umss-navy tracking-tight">
                        UMSS <span class="font-normal text-umss-gray-700">Cursos</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6 text-sm font-medium">
                    <a href="/" class="text-umss-gray-700 hover:text-umss-navy transition">Catálogo</a>
                    <a href="/verificar-certificado" class="text-umss-gray-700 hover:text-umss-navy transition">Verificar Certificado</a>
                    @auth
                        @role(\App\Enums\UserRole::ADMIN->value)
                            <a href="/admin" class="btn-primary !h-9 !px-3 !text-xs">Panel Admin</a>
                        @elserole(\App\Enums\UserRole::INSTRUCTOR->value)
                            <a href="/instructor" class="btn-primary !h-9 !px-3 !text-xs">Panel Docente</a>
                        @elserole(\App\Enums\UserRole::STUDENT->value)
                            <a href="/estudiante" class="btn-primary !h-9 !px-3 !text-xs">Mi panel</a>
                        @endrole

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-umss-gray-700 hover:text-umss-navy transition text-xs font-medium">Salir</button>
                        </form>
                    @else
                        <a href="/login" class="btn-primary !h-9 !px-4 !text-xs">Iniciar Sesión</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    @if (session('success'))
        <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
            <div class="bg-umss-green-light border border-umss-green text-umss-green-dark px-4 py-3 rounded-lg text-sm flex items-center gap-2 font-medium shadow-sm">
                <svg class="w-4 h-4 flex-shrink-0 text-umss-green-dark" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
            <div class="bg-umss-red/10 border border-umss-red text-umss-red-dark px-4 py-3 rounded-lg text-sm flex items-center gap-2 font-medium shadow-sm">
                <svg class="w-4 h-4 flex-shrink-0 text-umss-red" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 flex-1 w-full">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-umss-gray-100 py-6 text-center text-umss-gray-700 text-xs">
        &copy; {{ date('Y') }} Universidad Mayor de San Simón — Cursos
    </footer>

    @livewireScripts
</body>
</html>
