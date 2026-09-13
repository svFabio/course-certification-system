<div>
    <div class="max-w-2xl mx-auto card-umss p-8">
        <h1 class="text-2xl font-semibold text-[#0E2E5F] mb-2">Formulario de Preinscripción</h1>
        <p class="text-sm text-[#4A4A4A] mb-6">Complete sus datos para reservar su cupo en el curso seleccionado.</p>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Grupo</label>
                <select wire:model="groupId" class="input-umss" required>
                    <option value="">Seleccione un grupo</option>
                    {{-- Groups populated dynamically --}}
                </select>
                @error('groupId') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Cédula de Identidad (CI)</label>
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
                    <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Correo Electrónico</label>
                    <input type="email" wire:model="email" placeholder="correo@ejemplo.com" class="input-umss" required>
                    @error('email') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Tipo de Participante</label>
                <select wire:model="tipoParticipante" class="input-umss" required>
                    <option value="">Seleccione su categoría</option>
                    <option value="umss">Comunidad UMSS (Estudiante / Docente)</option>
                    <option value="externo">Participante Externo</option>
                    <option value="auxiliar">Auxiliar de Docencia</option>
                </select>
                @error('tipoParticipante') <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="btn-primary w-full !h-11"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Confirmar Preinscripción</span>
                    <span wire:loading>Enviando solicitud...</span>
                </button>
            </div>
        </form>
    </div>
</div>
