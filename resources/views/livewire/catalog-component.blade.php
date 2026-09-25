<div wire:poll.10s>
    <div class="card-umss p-6 mb-8">
        <h1 class="text-2xl font-semibold text-umss-navy mb-4">Catalogo de Cursos</h1>
        <div class="mb-4">
            <input type="text" wire:model.live="search" placeholder="Buscar cursos por nombre..."
                class="input-umss">
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-44">
                <select wire:model.live="periodo" class="input-umss">
                    <option value="">Todos los periodos</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <select wire:model.live="nivel" class="input-umss">
                    <option value="">Todos los niveles</option>
                    <option value="Básico">Básico</option>
                    <option value="Intermedio">Intermedio</option>
                    <option value="Avanzado">Avanzado</option>
                </select>
            </div>
            <div class="w-full sm:w-44">
                <select wire:model.live="cargaHoraria" class="input-umss">
                    <option value="">Todas las cargas</option>
                    <option value="20">20 horas</option>
                    <option value="30">30 horas</option>
                </select>
            </div>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card-umss p-12 text-center">
            <p class="text-umss-gray-700 text-base">No se encontraron cursos publicados.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($courses as $course)
                <div class="card-umss flex flex-col justify-between overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-md bg-umss-gray-100 text-umss-gray-700 border border-umss-gray-100">
                                {{ $course->periodo }}
                            </span>
                            <span class="text-xs font-medium text-umss-gray-700">
                                {{ $course->nivel }}
                            </span>
                        </div>
                        <h2 class="text-lg font-semibold text-umss-black mb-2 leading-snug">{{ $course->nombre }}</h2>
                        <p class="text-umss-gray-700 text-sm mb-4 line-clamp-2 leading-relaxed">{{ $course->contenido }}</p>

                        <div class="space-y-1.5 text-xs text-umss-gray-700 pt-3 border-t border-umss-gray-100">
                            <p><span class="font-medium text-umss-black">Docente:</span> {{ $course->instructor->name ?? 'Por asignar' }}</p>
                            <p><span class="font-medium text-umss-black">Carga horaria:</span> {{ $course->carga_horaria }} horas</p>
                        </div>

                        <div class="mt-4 pt-4 border-t border-umss-gray-100">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-umss-gray-700">UMSS</span>
                                <span class="font-semibold text-umss-navy">Bs. {{ number_format($course->precio_umss, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-umss-gray-700">Externo</span>
                                <span class="font-medium text-umss-gray-700">Bs. {{ number_format($course->precio_externo, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-umss-gray-700">Auxiliar</span>
                                <span class="font-medium text-umss-gray-700">Bs. {{ number_format($course->precio_auxiliar, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-umss-gray-100 border-t border-umss-gray-100 space-y-2">
                        @if ($course->groups->isEmpty())
                            <p class="text-xs text-umss-gray-700 text-center">Sin grupos disponibles</p>
                        @else
                            @foreach ($course->groups as $group)
                                @php
                                    $inscritos = $group->inscritos_count ?? 0;
                                    $disponibles = max(0, $group->cupo_maximo - $inscritos);
                                    $full = $disponibles === 0;
                                @endphp
                                <div class="flex justify-between items-center text-xs">
                                    <div>
                                        <span class="font-medium text-umss-black">{{ $group->nombre }}</span>
                                        <span class="text-umss-gray-700 ml-1">{{ $group->hora_inicio instanceof \Carbon\Carbon ? $group->hora_inicio->format('H:i') : $group->hora_inicio }} - {{ $group->hora_fin instanceof \Carbon\Carbon ? $group->hora_fin->format('H:i') : $group->hora_fin }}</span>
                                        <span class="text-umss-gray-700 ml-1">({{ $inscritos }}/{{ $group->cupo_maximo }})</span>
                                    </div>
                                    @if ($full)
                                        <span class="text-umss-red font-medium">Lleno</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span class="text-umss-gray-700">{{ $disponibles }} {{ Str::plural('cupo', $disponibles) }} disponibles</span>
                                            <a href="{{ route('preinscripcion', ['group' => $group->id]) }}"
                                                class="text-umss-navy font-medium hover:underline">
                                                Preinscribirse
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $courses->links() }}
        </div>
    @endif
</div>