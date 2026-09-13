<div>
    <div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Verificar Certificado</h1>

        <form wire:submit="verify" class="flex gap-4 mb-6">
            <input type="text" wire:model="codigo" placeholder="Ingrese el código del certificado"
                class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Verificar
            </button>
        </form>

        @if ($searched)
            @if ($certificate)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="text-lg font-bold text-green-800 mb-4">Certificado Válido</h2>
                    <div class="space-y-2 text-sm text-gray-700">
                        <p><span class="font-medium">Participante:</span> {{ $certificate['nombre'] ?? 'N/A' }}</p>
                        <p><span class="font-medium">Curso:</span> {{ $certificate['curso'] ?? 'N/A' }}</p>
                        <p><span class="font-medium">Tipo:</span> {{ $certificate['tipo'] ?? 'N/A' }}</p>
                        <p><span class="font-medium">Fecha de emisión:</span> {{ $certificate['emitido_en'] ?? 'N/A' }}</p>
                        <p><span class="font-medium">Estado:</span> {{ $certificate['signature_status'] ?? 'N/A' }}</p>
                        <p><span class="font-medium">Código:</span> {{ $certificate['codigo_unico'] ?? 'N/A' }}</p>
                    </div>
                    @if (isset($certificate['qr_url']))
                        <div class="mt-4 text-center">
                            <img src="{{ $certificate['qr_url'] }}" alt="QR Code" class="inline-block w-32 h-32">
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-800 font-medium">No se encontró ningún certificado con ese código.</p>
                    <p class="text-red-600 text-sm mt-1">Verifique el código e intente nuevamente.</p>
                </div>
            @endif
        @endif
    </div>
</div>
