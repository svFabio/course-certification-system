<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'UMSS - Formación Continua') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-800">UMSS - Formación Continua</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-800">Catálogo</a>
                    <a href="/verificar-certificado" class="text-gray-600 hover:text-gray-800">Verificar Certificado</a>
                    <a href="/asistencia" class="text-gray-600 hover:text-gray-800">Asistencia</a>
                    <a href="/admin" class="text-gray-600 hover:text-gray-800">Admin</a>
                    <a href="/instructor" class="text-gray-600 hover:text-gray-800">Instructor</a>
                </div>
            </div>
        </div>
    </nav>

    @if (session('success'))
        <div class="max-w-7xl mx-auto mt-4 px-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto mt-4 px-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="bg-white mt-12 py-6 text-center text-gray-500 text-sm">
        &copy; {{ date('Y') }} UMSS - Universidad Mayor de San Simón
    </footer>

    @livewireScripts
</body>
</html>
