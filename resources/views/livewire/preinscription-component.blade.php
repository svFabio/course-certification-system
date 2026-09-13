<div>
    <div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Formulario de Preinscripción</h1>

        <form wire:submit="submit" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                <select wire:model="groupId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Seleccione un grupo</option>
                    {{-- Groups populated dynamically --}}
                </select>
                @error('groupId') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CI</label>
                    <input type="text" wire:model="ci" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('ci') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombres</label>
                    <input type="text" wire:model="nombres" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('nombres') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido Paterno</label>
                    <input type="text" wire:model="apellidoPaterno" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('apellidoPaterno') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido Materno</label>
                    <input type="text" wire:model="apellidoMaterno" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('apellidoMaterno') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                    <input type="tel" wire:model="celular" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('celular') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Participante</label>
                <select wire:model="tipoParticipante" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Seleccione</option>
                    <option value="umss">UMSS</option>
                    <option value="externo">Externo</option>
                    <option value="auxiliar">Auxiliar</option>
                </select>
                @error('tipoParticipante') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Enviar Preinscripción</span>
                <span wire:loading>Enviando...</span>
            </button>
        </form>
    </div>
</div>
