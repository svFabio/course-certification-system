<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'UMSS - Formación Continua') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#F5F5F5] text-[#121212] font-sans min-h-screen flex flex-col antialiased">
    <nav class="bg-white border-b border-[#E5E5E5] sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <a href="/" class="text-xl font-semibold text-[#0E2E5F] tracking-tight">
                        UMSS <span class="font-normal text-[#4A4A4A]">| Formación Continua</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6 text-sm font-medium">
                    <a href="/" class="text-[#4A4A4A] hover:text-[#0E2E5F] transition">Catálogo</a>
                    <a href="/verificar-certificado" class="text-[#4A4A4A] hover:text-[#0E2E5F] transition">Verificar Certificado</a>
                    @auth
                        @if(auth()->user()->hasRole('admin'))
                            <a href="/admin" class="btn-primary !h-9 !px-3 !text-xs">Panel Admin</a>
                        @elseif(auth()->user()->hasRole('instructor'))
                            <a href="/instructor" class="btn-primary !h-9 !px-3 !text-xs">Panel Docente</a>
                        @elseif(auth()->user()->hasRole('student'))
                            <a href="/estudiante" class="btn-primary !h-9 !px-3 !text-xs">Mi panel</a>
                        @endif
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
            <div class="bg-rose-50 border border-[#E01D2E] text-[#8B0000] px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 flex-1 w-full">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-[#E5E5E5] py-6 text-center text-[#4A4A4A] text-xs">
        &copy; {{ date('Y') }} Universidad Mayor de San Simón — Formación Continua
    </footer>

    @livewireScripts
</body>
</html>
