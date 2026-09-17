<div>
    <div class="card-umss p-6 mb-8">
        <h1 class="text-2xl font-semibold text-[#0E2E5F] mb-4">Catalogo de Cursos</h1>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live="search" placeholder="Buscar cursos por nombre..."
                    class="input-umss">
            </div>
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
                    <option value="Basico">Basico</option>
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
            <p class="text-[#4A4A4A] text-base">No se encontraron cursos publicados.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($courses as $course)
                <div class="card-umss flex flex-col justify-between overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-md bg-[#F5F5F5] text-[#4A4A4A] border border-[#E5E5E5]">
                                {{ $course->periodo }}
                            </span>
                            <span class="text-xs font-medium text-[#4A4A4A]">
                                {{ $course->nivel }}
                            </span>
                        </div>
                        <h2 class="text-lg font-semibold text-[#121212] mb-2 leading-snug">{{ $course->nombre }}</h2>
                        <p class="text-[#4A4A4A] text-sm mb-4 line-clamp-2 leading-relaxed">{{ $course->contenido }}</p>

                        <div class="space-y-1.5 text-xs text-[#4A4A4A] pt-3 border-t border-[#E5E5E5]">
                            <p><span class="font-medium text-[#121212]">Docente:</span> {{ $course->instructor->name ?? 'Por asignar' }}</p>
                            <p><span class="font-medium text-[#121212]">Carga horaria:</span> {{ $course->carga_horaria }} horas</p>
                        </div>

                        <div class="mt-4 pt-4 border-t border-[#E5E5E5]">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-[#4A4A4A]">UMSS</span>
                                <span class="font-semibold text-[#0E2E5F]">Bs. {{ number_format($course->precio_umss, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-[#4A4A4A]">Externo</span>
                                <span class="font-medium text-[#4A4A4A]">Bs. {{ number_format($course->precio_externo, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-[#4A4A4A]">Auxiliar</span>
                                <span class="font-medium text-[#4A4A4A]">Bs. {{ number_format($course->precio_auxiliar, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-[#F5F5F5] border-t border-[#E5E5E5] space-y-2">
                        @if ($course->groups->isEmpty())
                            <p class="text-xs text-[#4A4A4A] text-center">Sin grupos disponibles</p>
                        @else
                            @foreach ($course->groups as $group)
                                @php
                                    $inscritos = $group->inscritos_count ?? 0;
                                    $full = $inscritos >= $group->cupo_maximo;
                                @endphp
                                <div class="flex justify-between items-center text-xs">
                                    <div>
                                        <span class="font-medium text-[#121212]">{{ $group->nombre }}</span>
                                        <span class="text-[#4A4A4A] ml-1">{{ $group->hora_inicio instanceof \Carbon\Carbon ? $group->hora_inicio->format('H:i') : $group->hora_inicio }} - {{ $group->hora_fin instanceof \Carbon\Carbon ? $group->hora_fin->format('H:i') : $group->hora_fin }}</span>
                                        <span class="text-[#4A4A4A] ml-1">({{ $inscritos }}/{{ $group->cupo_maximo }})</span>
                                    </div>
                                    @if ($full)
                                        <span class="text-[#E01D2E] font-medium">Lleno</span>
                                    @else
                                        <a href="{{ route('preinscripcion', ['group' => $group->id]) }}"
                                            class="text-[#0E2E5F] font-medium hover:underline">
                                            Preinscribirse
                                        </a>
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
