<div>
    <div class="max-w-2xl mx-auto card-umss p-8 bg-umss-white border border-umss-gray-200 border-t-4 border-t-umss-navy rounded-xl shadow-md">
        @if($isSubmitted && $registeredData)
            {{-- STEP 3: SUCCESS SCREEN --}}
            <div class="text-center py-4">
                <div class="w-16 h-16 bg-umss-green-light text-umss-green rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-umss-navy tracking-tight mb-2">¡Preinscripción Registrada Exitosamente!</h1>
                <p class="text-sm text-umss-gray-700 max-w-md mx-auto mb-6">
                    Su reserva de cupo para el curso ha sido registrada en el sistema. Siga las instrucciones abajo para consolidar su inscripción formal.
                </p>

                <div class="bg-umss-gray-100 border border-umss-gray-200 rounded-xl p-5 text-left mb-6 space-y-2.5">
                    <div class="flex justify-between text-sm">
                        <span class="text-xs text-umss-gray-700 uppercase font-semibold">Participante</span>
                        <span class="font-medium text-umss-black">{{ $registeredData['nombres'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-xs text-umss-gray-700 uppercase font-semibold">Cédula de Identidad (CI)</span>
                        <span class="font-medium text-umss-black">{{ $registeredData['ci'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-xs text-umss-gray-700 uppercase font-semibold">Curso</span>
                        <span class="font-medium text-umss-black">{{ $registeredData['curso'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-xs text-umss-gray-700 uppercase font-semibold">Grupo Asignado</span>
                        <span class="font-medium text-umss-black">{{ $registeredData['grupo'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-umss-gray-200 pt-2">
                        <span class="text-xs text-umss-gray-700 uppercase font-semibold">Monto a pagar</span>
                        <span class="font-bold text-umss-navy text-base">Bs. {{ number_format($registeredData['monto'], 2) }}</span>
                    </div>
                </div>

                <div class="p-4 bg-umss-amber-light border border-umss-amber rounded-xl text-left mb-8 flex gap-3">
                    <svg class="w-5 h-5 text-umss-amber flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-xs text-umss-amber-dark space-y-1">
                        <p class="font-semibold text-sm">Instrucciones para completar su pago:</p>
                        <p>1. Acérquese a <strong>Caja Facultativa de la UMSS</strong> indicando su CI y el nombre del curso.</p>
                        <p>2. Efectúe el pago correspondiente de <strong>Bs. {{ number_format($registeredData['monto'], 2) }}</strong>.</p>
                        <p>3. Conserve su comprobante. Su pago será validado por la administración académica y recibirá la confirmación formal por correo electrónico.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('home') }}" class="btn-primary !h-11 px-8 inline-flex items-center justify-center">
                        Volver al Catálogo de Cursos
                    </a>
                </div>
            </div>
        @elseif (! $stepConfirmation)
            <h1 class="text-2xl font-bold text-umss-navy tracking-tight mb-2">Formulario de Preinscripción</h1>
            <p class="text-xs text-umss-gray-700 mb-6">Complete sus datos para reservar su cupo en el curso seleccionado.</p>
            {{-- STEP 1: Form --}}
            <form wire:submit="goToConfirmation" class="space-y-5">
                <div>
                    <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Grupo</label>
                    <select wire:model.live="groupId" class="input-umss" required>
                        <option value="">Seleccione un grupo</option>
                        @foreach($availableGroups as $g)
                            <option value="{{ $g->id }}">
                                {{ $g->nombre }} — {{ $g->hora_inicio->format('H:i') }}-{{ $g->hora_fin->format('H:i') }}
                                ({{ $g->cupo_maximo - ($g->confirmed_count ?? 0) }} cupos)
                            </option>
                        @endforeach
                    </select>
                    @error('groupId') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Cedula de Identidad (CI)</label>
                        <input type="text" wire:model.blur="ci" placeholder="Ej. 7894561" class="input-umss" maxlength="15" required>
                        @error('ci') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Nombres</label>
                        <input type="text" wire:model.blur="nombres" placeholder="Nombres" class="input-umss" maxlength="100" required>
                        @error('nombres') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Apellido Paterno</label>
                        <input type="text" wire:model.blur="apellidoPaterno" placeholder="Apellido paterno" class="input-umss" maxlength="100" required>
                        @error('apellidoPaterno') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Apellido Materno</label>
                        <input type="text" wire:model.blur="apellidoMaterno" placeholder="Apellido materno" class="input-umss" maxlength="100">
                        @error('apellidoMaterno') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Celular de Contacto (8 dígitos)</label>
                        <input type="tel" inputmode="numeric" wire:model.blur="celular" placeholder="Ej. 71234567" maxlength="8" class="input-umss">
                        @error('celular') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Correo Electronico</label>
                        <input type="email" inputmode="email" wire:model.blur="email" placeholder="correo@ejemplo.com" class="input-umss" maxlength="150" required>
                        @error('email') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Tipo de Participante</label>
                    <select wire:model.live="tipoParticipante" class="input-umss" required>
                        <option value="">Seleccione su categoria</option>
                        <option value="umss">Comunidad UMSS (Estudiante / Docente)</option>
                        <option value="externo">Participante Externo</option>
                        <option value="auxiliar">Auxiliar de Docencia</option>
                    </select>
                    @error('tipoParticipante') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                @if(in_array($tipoParticipante, ['umss', 'auxiliar'], true))
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Código SIS (Opcional si cuenta con registro UMSS)</label>
                        <input type="text" inputmode="numeric" wire:model.blur="codSis" placeholder="Ej. 202002515" maxlength="10" class="input-umss">
                        @error('codSis') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($this->precioCalculado !== null)
                    <div class="bg-umss-gray-100 border border-umss-gray-100 rounded-lg p-4">
                        <p class="text-xs text-umss-gray-700 uppercase tracking-wide mb-1">Costo de inscripcion</p>
                        <p class="text-2xl font-bold text-umss-navy">Bs. {{ number_format($this->precioCalculado, 2) }}</p>
                    </div>
                @endif

                <div class="pt-4">
                    <button type="submit"
                        class="btn-primary w-full !h-11"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Continuar</span>
                        <span wire:loading>Validando...</span>
                    </button>
                </div>
            </form>
        @else
            {{-- STEP 2: Confirmation --}}
            <div class="space-y-4">
                <div class="bg-umss-gray-100 border border-umss-gray-100 rounded-lg p-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Nombre completo</span>
                        <span class="text-sm font-medium text-umss-black">{{ $nombres }} {{ $apellidoPaterno }} {{ $apellidoMaterno }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">CI</span>
                        <span class="text-sm font-medium text-umss-black">{{ $ci }}</span>
                    </div>
                    @if($codSis)
                        <div class="flex justify-between">
                            <span class="text-xs text-umss-gray-700 uppercase">Código SIS</span>
                            <span class="text-sm font-medium text-umss-black">{{ $codSis }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Email</span>
                        <span class="text-sm font-medium text-umss-black">{{ $email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Celular</span>
                        <span class="text-sm font-medium text-umss-black">{{ $celular ?: 'No registrado' }}</span>
                    </div>
                    <hr class="border-umss-gray-100">
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Curso</span>
                        <span class="text-sm font-medium text-umss-black">{{ $group->course->nombre }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Grupo</span>
                        <span class="text-sm font-medium text-umss-black">{{ $group->nombre }} ({{ $group->hora_inicio->format('H:i') }} - {{ $group->hora_fin->format('H:i') }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-umss-gray-700 uppercase">Tipo participante</span>
                        <span class="text-sm font-medium text-umss-black">{{ ucfirst($tipoParticipante) }}</span>
                    </div>
                    <hr class="border-umss-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-umss-gray-700 uppercase">Costo a pagar</span>
                        <span class="text-xl font-bold text-umss-navy">Bs. {{ number_format($this->precioCalculado, 2) }}</span>
                    </div>
                </div>

                <div class="bg-umss-gray-100 border border-umss-gray-100 rounded-lg p-4">
                    <p class="text-sm text-umss-navy font-medium mb-1">Siguiente paso:</p>
                    <p class="text-xs text-umss-gray-700">Acercarse a caja facultativa con su cedula de identidad para realizar el pago. Presentar el comprobante de pago para confirmar su inscripcion.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="backToEdit"
                        class="flex-1 border border-umss-gray-100 text-umss-gray-700 px-4 py-3 rounded-lg font-medium hover:bg-umss-gray-100 transition text-sm">
                        Volver a editar
                    </button>
                    <button type="button" wire:click="submit"
                        class="flex-1 btn-primary !h-11"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Confirmar preinscripcion</span>
                        <span wire:loading>Enviando...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
