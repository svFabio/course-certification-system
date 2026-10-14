<x-filament-panels::page>
    {{-- Estado actual --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-2">
        <div @class([
            'rounded-xl border px-4 py-3',
            'border-umss-green bg-umss-green-light' => $isSheetsConfigured,
            'border-umss-amber bg-umss-amber-light' => ! $isSheetsConfigured,
        ])>
            <p class="text-xs font-semibold uppercase tracking-wide {{ $isSheetsConfigured ? 'text-umss-green-dark' : 'text-umss-amber-dark' }}">
                Estado de exportación
            </p>
            <p class="mt-1 text-sm font-medium text-umss-black">
                {{ $isSheetsConfigured ? 'Lista para exportar planillas' : 'Pendiente de configuración' }}
            </p>
        </div>
        <div class="rounded-xl border border-umss-gray-200 bg-umss-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-umss-gray-700">Uso</p>
            <p class="mt-1 text-sm text-umss-black">
                Desde <strong>Grupos → Exportar a Google Sheets</strong> se generan las planillas de caja y docente.
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if(($data['auth_method'] ?? '') === 'oauth')
            <div class="border border-umss-gray-200 rounded-xl bg-umss-white overflow-hidden">
                <div class="px-6 py-4 bg-umss-gray-100 border-b border-umss-gray-200">
                    <h3 class="text-sm font-semibold text-umss-black">Conexión con Google</h3>
                </div>
                <div class="px-6 py-5">
                    @if(! $isOAuthEnvConfigured)
                        <div class="rounded-lg border border-umss-amber bg-umss-amber-light p-4 text-sm text-umss-amber-dark">
                            <p class="font-semibold">Faltan variables OAuth en el servidor</p>
                            <p class="mt-1">Configure en <code class="text-xs">.env</code>:</p>
                            <ul class="mt-2 list-disc pl-5 space-y-1 text-xs">
                                <li><code>GOOGLE_OAUTH_CLIENT_ID</code></li>
                                <li><code>GOOGLE_OAUTH_CLIENT_SECRET</code></li>
                                <li><code>GOOGLE_OAUTH_REDIRECT_URI={{ $oauthCallbackUri }}</code></li>
                            </ul>
                        </div>
                    @elseif($isOAuthConnected)
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-umss-green-light">
                                    <svg class="h-5 w-5 text-umss-green" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-umss-black">{{ $oauthUserEmail ?? 'Cuenta conectada' }}</p>
                                    <p class="text-xs text-umss-gray-700">Cuenta Google vinculada</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $oauthRedirectUrl }}" class="inline-flex items-center rounded-lg bg-umss-sky-light px-3 py-1.5 text-xs font-medium text-umss-sky hover:bg-umss-gray-200">
                                    Cambiar cuenta
                                </a>
                                <a href="{{ route('google-sheets.disconnect') }}" class="inline-flex items-center rounded-lg bg-umss-gray-100 px-3 py-1.5 text-xs font-medium text-umss-red hover:bg-umss-gray-200">
                                    Desvincular
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <p class="mb-4 text-sm text-umss-gray-700">Conecta tu cuenta de Google para crear planillas en tu Drive.</p>
                            <a href="{{ $oauthRedirectUrl }}"
                               class="inline-flex items-center justify-center gap-3 rounded-full border border-umss-gray-300 bg-umss-white px-6 py-3 text-sm font-medium text-umss-black shadow-sm hover:bg-umss-gray-100">
                                <span>Iniciar sesión con Google</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit">
                Guardar configuración
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="testConnection" wire:loading.attr="disabled">
                Probar conexión
            </x-filament::button>
        </div>
    </form>

    {{-- Guía rápida Service Account --}}
    @if(($data['auth_method'] ?? 'service_account') === 'service_account')
        <div class="mt-8 rounded-xl border border-umss-sky bg-umss-sky-light p-5">
            <h3 class="text-sm font-semibold text-umss-navy">Cómo obtener el JSON (Service Account)</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-umss-gray-700">
                <li>Abra <a class="font-medium text-umss-sky underline" href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>.</li>
                <li>Cree un proyecto (o use uno existente) → <strong>APIs y servicios → Biblioteca</strong> → active <strong>Google Sheets API</strong>.</li>
                <li>Vaya a <strong>Credenciales → Crear credenciales → Cuenta de servicio</strong>.</li>
                <li>En la cuenta creada → pestaña <strong>Claves → Agregar clave → JSON</strong> y descargue el archivo.</li>
                <li>Pegue el contenido del JSON en el campo de arriba y pulse <strong>Guardar</strong>.</li>
                <li>
                    Si ya tiene un spreadsheet, compártalo con el email de la service account
                    (<code class="text-xs">client_email</code> del JSON) con permiso de <strong>Editor</strong>,
                    y pegue el ID de la URL en <strong>Spreadsheet ID</strong>.
                </li>
                <li>Si deja el Spreadsheet ID vacío, al guardar se intentará crear uno nuevo automáticamente.</li>
            </ol>
        </div>
    @endif
</x-filament-panels::page>
