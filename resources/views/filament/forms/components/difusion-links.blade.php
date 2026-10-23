<div x-data="{ copied: false }" class="flex flex-wrap items-center gap-3 pt-1">
    <button type="button"
        @click="navigator.clipboard.writeText($wire.get('data.texto_difusion') || $wire.get('data.copy_redes') || document.querySelector('textarea[name*=\'texto_difusion\']')?.value || document.querySelector('textarea[name*=\'copy_redes\']')?.value || ''); copied = true; setTimeout(() => copied = false, 2500)"
        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-umss-gray-100 text-umss-gray-700 hover:text-umss-navy hover:bg-umss-gray-200 border border-umss-gray-300 transition shadow-sm">
        <svg class="w-4 h-4 text-umss-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span x-text="copied ? '¡Texto estructurado copiado!' : 'Copiar texto estructurado'"></span>
    </button>
    <span class="text-xs text-umss-gray-700">Estructura formateada con portada, aranceles y enlace directo, lista para copiar o difundir a Telegram.</span>
</div>
