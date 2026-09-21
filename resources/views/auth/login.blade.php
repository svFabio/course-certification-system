<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso Institucional — UMSS Cursos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-umss-gray-100 text-umss-black font-sans min-h-screen flex flex-col justify-between antialiased relative">
    {{-- Header / Back navigation on top-left corner --}}
    <header class="absolute top-6 left-6 z-10">
        <a href="/" class="inline-flex items-center gap-2 text-xs font-semibold text-umss-gray-700 hover:text-umss-navy transition py-2 px-3.5 rounded-lg bg-white/80 hover:bg-white shadow-sm border border-umss-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver al catálogo de cursos
        </a>
    </header>

    {{-- Main Login Card centered --}}
    <main class="flex-1 flex items-center justify-center px-4 py-16">
        <div class="w-full max-w-md">
            <div class="card-umss p-8 bg-white border border-umss-gray-100 border-t-4 border-t-umss-navy rounded-xl shadow-md">
            {{-- Institutional header --}}
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-umss-navy tracking-tight">
                    UMSS <span class="font-normal text-umss-gray-700">Cursos</span>
                </h1>
                <h2 class="text-lg font-semibold text-umss-navy mt-4">Acceso Institucional</h2>
                <p class="text-xs text-umss-gray-700 mt-1">Ingrese sus credenciales</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-umss-red text-umss-red-dark px-4 py-3 rounded-lg text-xs mb-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-center gap-1.5 font-medium">
                            <svg class="w-4 h-4 flex-shrink-0 text-umss-red" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                        Correo Electrónico
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy"
                        placeholder="usuario@umss.edu.bo"
                    >
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                        Contraseña
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy"
                        placeholder="••••••••"
                    >
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer select-none">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="rounded border-umss-gray-700 text-umss-navy focus:ring-umss-navy h-4 w-4"
                        >
                        <span class="ml-2 text-xs text-umss-gray-700">Recordar sesión</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="btn-primary w-full !h-11 bg-umss-navy hover:bg-umss-navy-dark text-umss-white font-semibold text-sm tracking-wide transition shadow-sm"
                    >
                        Iniciar Sesión
                    </button>
                </div>
            </form>
        </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="w-full text-center py-6 text-xs text-umss-gray-700">
        <p>&copy; {{ date('Y') }} Universidad Mayor de San Simón &mdash; Todos los derechos reservados.</p>
    </footer>
</body>
</html>
