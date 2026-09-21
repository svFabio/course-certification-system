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
            <div class="bg-emerald-50 border border-emerald-500 text-emerald-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
            <div class="bg-rose-50 border border-umss-red text-umss-red-dark px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
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
