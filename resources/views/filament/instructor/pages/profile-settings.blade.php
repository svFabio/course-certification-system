<x-filament-panels::page>
    <div class="mb-6 rounded-xl border border-umss-sky bg-umss-sky-light p-4">
        <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-umss-white text-umss-sky">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-umss-navy">Firma para Certificados de Participantes</p>
                <p class="mt-1 text-xs text-umss-gray-700">
                    Esta imagen se estampará sobre la línea de firma «Docente del curso» en los certificados emitidos a sus estudiantes una vez completado el flujo de firmas.
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit">
                Guardar Firma
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
