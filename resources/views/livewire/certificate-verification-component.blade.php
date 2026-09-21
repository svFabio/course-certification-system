<div>
    <div class="max-w-2xl mx-auto card-umss p-8 bg-white border border-umss-gray-100 border-t-4 border-t-umss-navy rounded-xl shadow-md">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-umss-navy tracking-tight">Verificación de Certificados</h1>
            <p class="text-xs text-umss-gray-700 mt-1">Consulte la autenticidad y estado de validez de certificados emitidos por la Universidad Mayor de San Simón.</p>
        </div>

        <form wire:submit="verify" class="flex flex-col sm:flex-row gap-3 mb-8">
            <div class="flex-1">
                <label for="codigo" class="sr-only">Código del Certificado</label>
                <input type="text" id="codigo" wire:model="codigo" placeholder="Ingrese el código único (ej. CERT-2024-XXXX)"
                    class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy" required>
            </div>
            <button type="submit" class="btn-primary !h-[42px] px-6 bg-umss-navy hover:bg-umss-navy-dark text-umss-white font-semibold text-sm tracking-wide transition shadow-sm whitespace-nowrap">
                Verificar
            </button>
        </form>

        @if ($searched)
            @if ($certificate)
                <div class="p-6 border border-emerald-200 border-l-4 border-l-emerald-600 bg-white rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 mb-5 pb-3 border-b border-umss-gray-100">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">✓</span>
                        <div>
                            <h2 class="text-base font-semibold text-emerald-900 leading-tight">Certificado Válido y Registrado</h2>
                            <p class="text-xs text-umss-gray-700">Documento verificado en la base de datos oficial.</p>
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Participante</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['nombre'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Curso</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['curso'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Modalidad / Tipo</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['tipo'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Fecha de Emisión</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['emitido_en'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Firma Digital</dt>
                            <dd class="font-medium text-umss-navy capitalize mt-0.5">{{ $certificate['signature_status'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Código Único</dt>
                            <dd class="font-mono text-xs font-bold text-umss-black mt-0.5">{{ $certificate['codigo_unico'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                    @if (isset($certificate['qr_url']))
                        <div class="mt-6 pt-4 border-t border-umss-gray-100 text-center">
                            <img src="{{ $certificate['qr_url'] }}" alt="Código QR de Verificación" class="inline-block w-32 h-32 border border-umss-gray-100 rounded-lg p-1 bg-white">
                        </div>
                    @endif
                </div>
            @else
                <div class="p-6 border border-red-200 border-l-4 border-l-umss-red bg-white rounded-lg text-center shadow-sm">
                    <p class="text-umss-red font-semibold text-sm">No se encontró ningún certificado con ese código</p>
                    <p class="text-umss-gray-700 text-xs mt-1">Verifique que el código coincida exactamente con el impreso en el documento oficial.</p>
                </div>
            @endif
        @endif
    </div>
</div>
