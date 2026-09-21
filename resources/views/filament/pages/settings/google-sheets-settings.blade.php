<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        @if(($data['auth_method'] ?? '') === 'oauth')
            {{-- Google Connection Card --}}
            <div class="mt-6 border border-gray-200 rounded-xl bg-white overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Conexión con Google</h3>
                </div>

                {{-- Content --}}
                <div class="px-6 py-5">
                    @if(\App\Services\GoogleSheetsService::isOAuthConnected())
                        {{-- Connected State --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $oauthUserEmail ?? 'Cuenta conectada' }}</p>
                                    <p class="text-xs text-gray-500">Tu cuenta de Google está vinculada</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ \App\Services\GoogleSheetsService::getOAuthRedirectUrl() }}"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Cambiar cuenta
                                </a>
                                <a href="{{ route('google-sheets.disconnect') }}"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Desvincular
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- Disconnected State --}}
                        <div class="text-center py-4">
                            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Conecta tu cuenta de Google para crear planillas en tu Drive</p>

                            {{-- Google Sign-In Button --}}
                            <a href="{{ \App\Services\GoogleSheetsService::getOAuthRedirectUrl() }}"
                               class="inline-flex items-center justify-center gap-3 w-full max-w-xs px-6 py-3 bg-white border border-[#747775] text-[#1f1f1f] text-sm font-medium rounded-full hover:bg-gray-50 hover:border-[#5f6368] shadow-sm transition-all"
                               style="font-family: 'Google Sans', Roboto, sans-serif;">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span>Iniciar sesión con Google</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-6">
            <x-filament::button type="submit">
                Guardar
            </x-filament::button>
        </div>
    </form>

    {{-- Instructions - Only for Admins/Devs --}}
    @if(($data['auth_method'] ?? '') === 'oauth' && !\App\Services\GoogleSheetsService::isOAuthConnected())
        <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-amber-800">
                    <p class="font-medium">¿Primera vez aquí?</p>
                    <p class="mt-1">Pide al administrador del sistema que configure las credenciales de Google antes de conectar tu cuenta.</p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
