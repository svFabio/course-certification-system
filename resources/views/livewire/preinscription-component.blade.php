<div>
    <div class="max-w-2xl mx-auto card-umss p-8 bg-white border border-umss-gray-100 border-t-4 border-t-umss-navy rounded-xl shadow-md">
        <h1 class="text-2xl font-bold text-umss-navy tracking-tight mb-2">Formulario de Preinscripción</h1>
        <p class="text-xs text-umss-gray-700 mb-6">Complete sus datos para reservar su cupo en el curso seleccionado.</p>

        @if (!$stepConfirmation)
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
                        <input type="text" wire:model="ci" placeholder="Ej. 7894561" class="input-umss" required>
                        @error('ci') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Nombres</label>
                        <input type="text" wire:model="nombres" placeholder="Nombres" class="input-umss" required>
                        @error('nombres') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Apellido Paterno</label>
                        <input type="text" wire:model="apellidoPaterno" placeholder="Apellido paterno" class="input-umss" required>
                        @error('apellidoPaterno') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Apellido Materno</label>
                        <input type="text" wire:model="apellidoMaterno" placeholder="Apellido materno" class="input-umss">
                        @error('apellidoMaterno') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Celular de Contacto</label>
                        <input type="tel" wire:model="celular" placeholder="Ej. 71234567" class="input-umss">
                        @error('celular') <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-umss-gray-700 mb-1.5 uppercase tracking-wide">Correo Electronico</label>
                        <input type="email" wire:model="email" placeholder="correo@ejemplo.com" class="input-umss" required>
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
                        <input type="text" wire:model="codSis" placeholder="Ej. 202002515" class="input-umss">
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
