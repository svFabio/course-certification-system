<div>
    <div class="max-w-2xl mx-auto card-umss p-8">
        <h1 class="text-2xl font-semibold text-umss-navy mb-2">Verificación de Certificados</h1>
        <p class="text-sm text-umss-gray-700 mb-6">Consulte la autenticidad y estado de validez de certificados emitidos por la UMSS.</p>

        <form wire:submit="verify" class="flex flex-col sm:flex-row gap-3 mb-8">
            <input type="text" wire:model="codigo" placeholder="Ingrese el código único del certificado (ej. CERT-2024-XXXX)"
                class="input-umss flex-1" required>
            <button type="submit" class="btn-primary whitespace-nowrap">
                Verificar
            </button>
        </form>

        @if ($searched)
            @if ($certificate)
                <div class="card-umss p-6 border-l-4 border-l-emerald-600 bg-white">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">✓</span>
                        <h2 class="text-lg font-semibold text-emerald-900">Certificado Válido y Registrado</h2>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Participante</dt>
                            <dd class="font-medium text-umss-black">{{ $certificate['nombre'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Curso</dt>
                            <dd class="font-medium text-umss-black">{{ $certificate['curso'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Modalidad / Tipo</dt>
                            <dd class="font-medium text-umss-black">{{ $certificate['tipo'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Fecha de Emisión</dt>
                            <dd class="font-medium text-umss-black">{{ $certificate['emitido_en'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Firma Digital</dt>
                            <dd class="font-medium text-umss-navy capitalize">{{ $certificate['signature_status'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-umss-gray-700 uppercase tracking-wide">Código Único</dt>
                            <dd class="font-mono text-xs font-semibold text-umss-black">{{ $certificate['codigo_unico'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                    @if (isset($certificate['qr_url']))
                        <div class="mt-6 pt-4 border-t border-umss-gray-100 text-center">
                            <img src="{{ $certificate['qr_url'] }}" alt="Código QR de Verificación" class="inline-block w-32 h-32 border border-umss-gray-100 rounded-lg p-1">
                        </div>
                    @endif
                </div>
            @else
                <div class="card-umss p-6 border-l-4 border-l-umss-red bg-white text-center">
                    <p class="text-umss-red font-semibold text-base">No se encontró ningún certificado con ese código</p>
                    <p class="text-umss-gray-700 text-xs mt-1">Verifique que el código coincida exactamente con el impreso en el documento.</p>
                </div>
            @endif
        @endif
    </div>
</div>
