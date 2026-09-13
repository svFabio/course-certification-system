<div>
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Catálogo de Cursos</h1>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live="search" placeholder="Buscar cursos..."
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-full sm:w-48">
                <select wire:model.live="periodo" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los períodos</option>
                    <option value="2024-I">2024-I</option>
                    <option value="2024-II">2024-II</option>
                    <option value="2025-I">2025-I</option>
                </select>
            </div>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="bg-white shadow rounded-lg p-12 text-center">
            <p class="text-gray-500 text-lg">No se encontraron cursos publicados.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($courses as $course)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">{{ $course->nombre }}</h2>
                        <p class="text-gray-600 mb-4">{{ $course->contenido }}</p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <p><span class="font-medium">Instructor:</span> {{ $course->instructor->name ?? 'N/A' }}</p>
                            <p><span class="font-medium">Carga horaria:</span> {{ $course->carga_horaria }} horas</p>
                            <p><span class="font-medium">Nivel:</span> {{ $course->nivel }}</p>
                            <p><span class="font-medium">Período:</span> {{ $course->periodo }}</p>
                            @if ($course->groups->isNotEmpty())
                                <p><span class="font-medium">Grupos disponibles:</span> {{ $course->groups->count() }}</p>
                            @endif
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-lg font-bold text-blue-600">Bs. {{ $course->precio_umss }}</p>
                            <p class="text-xs text-gray-400">Precio UMSS (externo: Bs. {{ $course->precio_externo }})</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3">
                        <a href="{{ route('preinscripcion', ['group' => $course->groups->first()?->id]) }}"
                            class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            Preinscribirse
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
