<div>
    <div class="card-umss p-6 mb-8">
        <h1 class="text-2xl font-semibold text-[#0E2E5F] mb-4">Catálogo de Cursos</h1>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live="search" placeholder="Buscar cursos por nombre o contenido..."
                    class="input-umss">
            </div>
            <div class="w-full sm:w-56">
                <select wire:model.live="periodo" class="input-umss">
                    <option value="">Todos los períodos</option>
                    <option value="2024-I">2024-I</option>
                    <option value="2024-II">2024-II</option>
                    <option value="2025-I">2025-I</option>
                </select>
            </div>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card-umss p-12 text-center">
            <p class="text-[#4A4A4A] text-base">No se encontraron cursos publicados en este período.</p>
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
                            @if ($course->groups->isNotEmpty())
                                <p><span class="font-medium text-[#121212]">Grupos disponibles:</span> {{ $course->groups->count() }}</p>
                            @endif
                        </div>

                        <div class="mt-4 pt-4 border-t border-[#E5E5E5] flex items-baseline justify-between">
                            <div>
                                <p class="text-xl font-semibold text-[#0E2E5F]">Bs. {{ number_format($course->precio_umss, 2) }}</p>
                                <p class="text-[11px] text-[#4A4A4A]">Comunidad UMSS</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-[#4A4A4A]">Bs. {{ number_format($course->precio_externo, 2) }}</p>
                                <p class="text-[11px] text-[#4A4A4A]">Externos</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-[#F5F5F5] border-t border-[#E5E5E5]">
                        <a href="{{ route('preinscripcion', ['group' => $course->groups->first()?->id]) }}"
                            class="btn-primary w-full text-center">
                            Preinscribirse
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
