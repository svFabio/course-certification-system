<x-filament-panels::page>
    {{-- Header Banner con Logo de Google Sheets y Estado --}}
    <div class="rounded-2xl border border-umss-gray-200 bg-umss-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                {{-- Logo oficial de Google Sheets --}}
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-umss-green-light border border-umss-green shadow-inner">
                    <svg class="h-8 w-8" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M25 5H10C8.34315 5 7 6.34315 7 8V32C7 33.6569 8.34315 35 10 35H30C31.6569 35 33 33.6569 33 32V13L25 5Z" fill="#0F9D58"/>
                        <path d="M25 5V13H33L25 5Z" fill="#87CEAB"/>
                        <path d="M13 18H27V20.5H13V18Z" fill="#FFFFFF"/>
                        <path d="M13 23H27V25.5H13V23Z" fill="#FFFFFF"/>
                        <path d="M13 28H27V30.5H13V28Z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-umss-navy">Integración con Google Sheets</h2>
                    <p class="text-xs text-umss-gray-700">Exportación de planillas docentes, asistencia y control de caja</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($isSheetsConfigured)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-umss-green-light px-3 py-1 text-xs font-semibold text-umss-green-dark">
                        <span class="h-2 w-2 rounded-full bg-umss-green"></span>
                        Conectado
                    </span>
                    @if($spreadsheetUrl)
                        <a href="{{ $spreadsheetUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-umss-gray-200 bg-umss-white px-3 py-1 text-xs font-medium text-umss-navy hover:bg-umss-gray-100 transition shadow-sm">
                            <span>Abrir libro</span>
                            <svg class="h-3.5 w-3.5 text-umss-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-umss-amber-light px-3 py-1 text-xs font-semibold text-umss-amber-dark">
                        <span class="h-2 w-2 rounded-full bg-umss-amber"></span>
                        Pendiente de configuración
                    </span>
                @endif
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if(($data['auth_method'] ?? '') === 'oauth')
            <div class="rounded-xl border border-umss-gray-200 bg-umss-white p-5 shadow-sm">
                <div class="flex items-center gap-3 pb-3 border-b border-umss-gray-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-umss-black">Conexión de Cuenta de Google</h3>
                </div>

                <div class="pt-4">
                    @if(! $isOAuthEnvConfigured)
                        <div class="rounded-lg border border-umss-amber bg-umss-amber-light p-3.5 text-xs text-umss-amber-dark">
                            <p class="font-semibold">Faltan variables OAuth en el archivo .env:</p>
                            <code class="mt-1 block font-mono text-[11px]">GOOGLE_OAUTH_CLIENT_ID, GOOGLE_OAUTH_CLIENT_SECRET, GOOGLE_OAUTH_REDIRECT_URI={{ $oauthCallbackUri }}</code>
                        </div>
                    @elseif($isOAuthConnected)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-umss-green-light">
                                    <svg class="h-5 w-5 text-umss-green" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-umss-black">{{ $oauthUserEmail ?? 'Cuenta conectada' }}</p>
                                    <p class="text-xs text-umss-gray-700">Cuenta de Google vinculada</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ $oauthRedirectUrl }}" class="inline-flex items-center rounded-lg bg-umss-sky-light px-3 py-1.5 text-xs font-semibold text-umss-sky hover:bg-umss-gray-200 transition">
                                    Cambiar cuenta
                                </a>
                                <a href="{{ route('google-sheets.disconnect') }}" class="inline-flex items-center rounded-lg bg-umss-gray-100 px-3 py-1.5 text-xs font-semibold text-umss-red hover:bg-umss-gray-200 transition">
                                    Desvincular
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-umss-gray-700">Conecte su cuenta para exportar planillas directamente a su unidad de Google Drive.</p>
                            <a href="{{ $oauthRedirectUrl }}"
                               class="inline-flex items-center gap-2.5 rounded-lg border border-umss-gray-300 bg-umss-white px-4 py-2 text-xs font-semibold text-umss-black shadow-sm transition hover:bg-umss-gray-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Iniciar sesión con Google</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <x-filament::button type="submit" size="md">
                Guardar cambios
            </x-filament::button>
            <x-filament::button type="button" color="gray" size="md" wire:click="testConnection" wire:loading.attr="disabled">
                Probar conexión
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
