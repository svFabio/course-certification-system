<x-filament-panels::page>
    <div class="mb-6 rounded-xl border border-umss-sky bg-umss-sky-light p-4">
        <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-umss-white text-umss-sky">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-umss-navy">Estampado Digital de Firmas (Modelo Híbrido)</p>
                <p class="mt-1 text-xs text-umss-gray-700">
                    Las imágenes PNG con fondo transparente cargadas en este panel se estamparán automáticamente sobre las líneas de firma en los certificados PDF autogenerados. Si no se carga ninguna imagen, el certificado mantendrá las líneas en blanco para firma física tradicional.
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit">
                Guardar Firmas Institucionales
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
