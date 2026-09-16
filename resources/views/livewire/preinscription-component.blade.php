<div>
    <div class="max-w-2xl mx-auto card-umss p-8">
        <h1 class="text-2xl font-semibold text-[#0E2E5F] mb-2">Formulario de Preinscripcion</h1>
        <p class="text-sm text-[#4A4A4A] mb-6">Complete sus datos para reservar su cupo en el curso seleccionado.</p>

        @if (!$stepConfirmation)
            {{-- STEP 1: Form --}}
            <form wire:submit="goToConfirmation" class="space-y-5">
                <div>
                    <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Grupo</label>
                    <select wire:model="groupId" class="input-umss" required>
                        <option value="">Seleccione un grupo</option>
                        @foreach($availableGroups as $g)
                            <option value="{{ $g->id }}">
                                {{ $g->nombre }} — {{ substr($g->hora_inicio, 0, 5) }}-{{ substr($g->hora_fin, 0, 5) }}
                                ({{ $g->cupo_maximo - ($g->confirmed_count ?? 0) }} cupos)
                            </option>
                        @endforeach
                    </select>
                    @error('groupId') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Cedula de Identidad (CI)</label>
                        <input type="text" wire:model="ci" placeholder="Ej. 7894561" class="input-umss" required>
                        @error('ci') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Nombres</label>
                        <input type="text" wire:model="nombres" placeholder="Nombres" class="input-umss" required>
                        @error('nombres') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Apellido Paterno</label>
                        <input type="text" wire:model="apellidoPaterno" placeholder="Apellido paterno" class="input-umss" required>
                        @error('apellidoPaterno') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Apellido Materno</label>
                        <input type="text" wire:model="apellidoMaterno" placeholder="Apellido materno" class="input-umss">
                        @error('apellidoMaterno') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Celular de Contacto</label>
                        <input type="tel" wire:model="celular" placeholder="Ej. 71234567" class="input-umss">
                        @error('celular') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Correo Electronico</label>
                        <input type="email" wire:model="email" placeholder="correo@ejemplo.com" class="input-umss" required>
                        @error('email') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Tipo de Participante</label>
                    <select wire:model="tipoParticipante" class="input-umss" required>
                        <option value="">Seleccione su categoria</option>
                        <option value="umss">Comunidad UMSS (Estudiante / Docente)</option>
                        <option value="externo">Participante Externo</option>
                        <option value="auxiliar">Auxiliar de Docencia</option>
                    </select>
                    @error('tipoParticipante') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                @if($this->precioCalculado !== null)
                    <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-lg p-4">
                        <p class="text-xs text-[#4A4A4A] uppercase tracking-wide mb-1">Costo de inscripcion</p>
                        <p class="text-2xl font-bold text-[#0E2E5F]">Bs. {{ number_format($this->precioCalculado, 2) }}</p>
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
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-lg p-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Nombre completo</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $nombres }} {{ $apellidoPaterno }} {{ $apellidoMaterno }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">CI</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $ci }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Email</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Celular</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $celular ?: 'No registrado' }}</span>
                    </div>
                    <hr class="border-[#E5E5E5]">
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Curso</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $group->course->nombre }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Grupo</span>
                        <span class="text-sm font-medium text-[#121212]">{{ $group->nombre }} ({{ substr($group->hora_inicio, 0, 5) }} - {{ substr($group->hora_fin, 0, 5) }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-[#4A4A4A] uppercase">Tipo participante</span>
                        <span class="text-sm font-medium text-[#121212]">{{ ucfirst($tipoParticipante) }}</span>
                    </div>
                    <hr class="border-[#E5E5E5]">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-[#4A4A4A] uppercase">Costo a pagar</span>
                        <span class="text-xl font-bold text-[#0E2E5F]">Bs. {{ number_format($this->precioCalculado, 2) }}</span>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-[#0E2E5F] font-medium mb-1">Siguiente paso:</p>
                    <p class="text-xs text-[#4A4A4A]">Acercarse a caja facultativa con su cedula de identidad para realizar el pago. Presentar el comprobante de pago para confirmar su inscripcion.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="backToEdit"
                        class="flex-1 border border-[#E5E5E5] text-[#4A4A4A] px-4 py-3 rounded-lg font-medium hover:bg-[#F5F5F5] transition text-sm">
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
