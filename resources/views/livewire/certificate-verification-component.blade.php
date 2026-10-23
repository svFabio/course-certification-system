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
                @php
                    $isValid = (bool) ($certificate['valido'] ?? false);
                    $border = $isValid ? 'border-umss-green/40 border-l-umss-green' : 'border-umss-amber/40 border-l-umss-amber';
                    $iconBg = $isValid ? 'bg-umss-green-light text-umss-green-dark' : 'bg-umss-amber-light text-umss-amber';
                    $titleColor = $isValid ? 'text-umss-green-dark' : 'text-umss-amber';
                @endphp
                <div class="p-6 border {{ $border }} border-l-4 bg-white rounded-lg shadow-sm">
                    <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-umss-gray-100">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $iconBg }} text-xs font-bold">
                            {{ $isValid ? '✓' : '…' }}
                        </span>
                        <div>
                            <h2 class="text-base font-semibold {{ $titleColor }} leading-tight">
                                {{ $certificate['estado_validez'] ?? 'Certificado registrado' }}
                            </h2>
                            <p class="text-xs text-umss-gray-700">Documento verificado en la base de datos oficial.</p>
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">
                                {{ $certificate['rol'] ?? 'Participante' }}
                            </dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['nombre'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Curso</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['curso'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Tipo</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['tipo'] ?? 'N/A' }}</dd>
                        </div>
                        @if (! empty($certificate['nota_final']))
                            <div>
                                <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Nota final</dt>
                                <dd class="font-bold text-umss-navy mt-0.5">{{ $certificate['nota_final'] }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Carga horaria</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['carga_horaria'] ?? 'N/A' }}</dd>
                        </div>
                        @if (! empty($certificate['grupos']))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Grupo(s) dictados</dt>
                                <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['grupos'] }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Fechas del curso</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['fechas_curso'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Fecha de emisión</dt>
                            <dd class="font-medium text-umss-black mt-0.5">{{ $certificate['emitido_en'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-umss-gray-700 uppercase tracking-wider">Código único</dt>
                            <dd class="font-mono text-xs font-bold text-umss-black mt-0.5">{{ $certificate['codigo_unico'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>

                    @if (! empty($certificate['download_url']))
                        <div class="mt-6 pt-5 border-t border-umss-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="text-xs text-umss-gray-700 leading-relaxed text-center sm:text-left">
                                Documento oficial verificable emitido por la Universidad Mayor de San Simón.
                            </p>
                            <a href="{{ $certificate['download_url'] }}" target="_blank"
                                class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 bg-umss-navy hover:bg-umss-navy-dark text-umss-white font-semibold text-sm tracking-wide rounded-lg shadow-sm transition whitespace-nowrap">
                                <svg class="w-4 h-4 text-umss-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Descargar Certificado PDF
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-6 border border-umss-red/30 border-l-4 border-l-umss-red bg-white rounded-lg text-center shadow-sm">
                    <p class="text-umss-red font-semibold text-sm">Certificado no encontrado. Verifique el código ingresado</p>
                    <p class="text-umss-gray-700 text-xs mt-1">Verifique que el código coincida exactamente con el impreso en el documento oficial.</p>
                </div>
            @endif
        @endif
    </div>
</div>
