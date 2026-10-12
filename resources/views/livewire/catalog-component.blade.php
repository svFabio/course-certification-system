<div>
    {{-- Search and filters header card --}}
    <div class="card-umss p-6 sm:p-8 mb-8 bg-white border border-umss-gray-100 border-t-4 border-t-umss-navy rounded-xl shadow-md">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-umss-navy tracking-tight">Catálogo de Cursos</h1>
            <p class="text-xs text-umss-gray-700 mt-1">Explore y encuentre los programas de formación continua disponibles en la UMSS.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="search" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                    Buscar curso
                </label>
                <div class="relative">
                    <input type="text" id="search" wire:model.live="search" placeholder="Escriba el nombre o palabra clave del curso..."
                        class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="periodo" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                        Periodo
                    </label>
                    <select id="periodo" wire:model.live="periodo" class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy">
                        <option value="">Todos los periodos</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="nivel" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                        Nivel
                    </label>
                    <select id="nivel" wire:model.live="nivel" class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy">
                        <option value="">Todos los niveles</option>
                        @foreach($this->niveles as $niv)
                            <option value="{{ $niv }}">{{ $niv }}</option>
                        @endforeach

                    </select>
                </div>
                <div>
                    <label for="cargaHoraria" class="block text-xs font-semibold text-umss-gray-700 uppercase tracking-wider mb-1.5">
                        Carga Horaria
                    </label>
                    <select id="cargaHoraria" wire:model.live="cargaHoraria" class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy">
                        <option value="">Todas las cargas</option>
                        @foreach($this->cargas as $c)
                            <option value="{{ $c }}">{{ $c }} horas</option>
                        @endforeach

                    </select>
                </div>
            </div>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card-umss p-12 text-center bg-white border border-umss-gray-100 rounded-xl shadow-sm">
            <p class="text-umss-navy font-semibold text-base">No se encontraron cursos publicados</p>
            <p class="text-xs text-umss-gray-700 mt-1">Intente cambiando los términos de búsqueda o los filtros seleccionados.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($courses as $course)
                <div class="card-umss bg-white border border-umss-gray-100 hover:border-umss-navy transition-all duration-200 rounded-xl shadow-sm hover:shadow-md flex flex-col justify-between overflow-hidden group"
                    x-data="{ open: false }">
                    {{-- Cover image header (approx. top 1/4 of card) --}}
                    <div class="relative h-36 w-full overflow-hidden bg-gradient-to-br from-umss-navy via-umss-navy to-umss-navy-dark flex items-center justify-center">
                        @if ($course->portada_url)
                            <img src="{{ $course->portada_url }}"
                                alt="Portada de {{ $course->nombre }}"
                                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
                                loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-umss-black/50 via-transparent to-transparent"></div>
                        @else
                            <div class="flex flex-col items-center justify-center text-white/70 select-none p-4 text-center">
                                <svg class="w-9 h-9 mb-1 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="text-[11px] font-medium tracking-wide uppercase text-white/90">Formación Continua UMSS</span>
                            </div>
                        @endif

                        <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 z-10">
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md bg-white/95 text-umss-navy shadow-sm backdrop-blur-sm">
                                {{ $course->periodo }}
                            </span>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md bg-umss-navy-dark/80 text-white shadow-sm backdrop-blur-sm border border-white/20">
                                {{ $course->nivel }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-umss-black mb-2 leading-snug">{{ $course->nombre }}</h2>
                            <p class="text-umss-gray-700 text-sm mb-4 line-clamp-2 leading-relaxed">{{ $course->contenido }}</p>
                        </div>

                        <div class="space-y-1.5 text-xs text-umss-gray-700 pt-3 border-t border-umss-gray-100">
                            <p><span class="font-medium text-umss-black">Docente:</span> {{ $course->instructor->name ?? 'Por asignar' }}</p>
                            <p><span class="font-medium text-umss-black">Carga horaria:</span> {{ $course->carga_horaria }} horas</p>
                        </div>
                    </div>

                    @if ($atCapacity[$course->id])
                        {{-- Course at capacity: single interest-registration entry point --}}
                        <div class="p-4 bg-umss-amber-light/60 border-t border-umss-gray-100">
                            @if ($interestSubmitted && $interestCourseId === $course->id)
                                @if ($interestAlreadyRegistered)
                                    <p class="text-xs text-umss-amber-dark font-medium text-center">
                                        Ya estás anotado(a) para este curso. Te avisaremos cuando haya cupo disponible.
                                    </p>
                                @else
                                    <p class="text-xs text-umss-green-dark font-medium text-center">
                                        ¡Listo! Te avisaremos por correo cuando haya cupo disponible en este curso.
                                    </p>
                                @endif
                            @else
                                <button type="button" wire:click="openInterest({{ $course->id }})"
                                    class="btn-primary w-full">
                                    Avisarme cuando haya cupo
                                </button>

                                @if ($interestCourseId === $course->id)
                                    <form wire:submit="registerInterest" class="mt-3 space-y-2">
                                        <input type="email" wire:model="interestEmail"
                                            placeholder="Tu correo electrónico"
                                            class="input-umss w-full text-sm font-sans focus:border-umss-navy focus:ring-1 focus:ring-umss-navy">
                                        @error('interestEmail')
                                            <p class="text-umss-red text-xs mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="btn-primary w-full">
                                                Registrarme
                                            </button>
                                            <button type="button" wire:click="closeInterest"
                                                class="shrink-0 text-xs font-semibold text-umss-gray-700 hover:text-umss-navy">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @else
                        <div class="p-4 bg-umss-gray-100/60 border-t border-umss-gray-100">
                            <button type="button" @click="open = true" aria-haspopup="dialog"
                                class="btn-primary w-full">
                                Preinscribirse
                            </button>
                        </div>
                    @endif

                    {{-- Preinscription details modal (prices and available groups) --}}
                    @if (! $atCapacity[$course->id])
                    <div x-show="open" x-cloak
                        @keydown.escape.window="open = false"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
                        role="dialog" aria-modal="true" aria-label="Preinscripción en {{ $course->nombre }}">
                        <div class="fixed inset-0 bg-umss-black/60 backdrop-blur-md" @click="open = false" aria-hidden="true"></div>

                        <div class="relative flex w-full max-w-xl flex-col overflow-hidden rounded-xl border border-umss-gray-200 bg-white shadow-xl max-h-[85vh]">
                            <div class="flex items-start justify-between gap-4 border-b border-umss-gray-200 px-6 py-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-umss-red">Preinscripción</p>
                                    <h3 class="text-base font-semibold leading-snug text-umss-black">{{ $course->nombre }}</h3>
                                </div>
                                <button type="button" @click="open = false" aria-label="Cerrar"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-umss-gray-700 transition hover:bg-umss-gray-100 hover:text-umss-navy focus:outline-none focus:ring-2 focus:ring-umss-navy">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-6 overflow-y-auto px-6 py-5">
                                <section aria-label="Precios">
                                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-umss-gray-700">Precios</h4>
                                    <div class="space-y-1.5 rounded-lg border border-umss-gray-200 bg-umss-gray-100/60 p-4">
                                        <div class="flex justify-between text-xs">
                                             <span class="text-umss-gray-700">UMSS</span>
                                             <span class="font-semibold text-umss-navy">Bs. {{ number_format((float) $course->precio_umss, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                             <span class="text-umss-gray-700">Externo</span>
                                             <span class="font-medium text-umss-gray-700">Bs. {{ number_format((float) $course->precio_externo, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                             <span class="text-umss-gray-700">Auxiliar</span>
                                             <span class="font-medium text-umss-gray-700">Bs. {{ number_format((float) $course->precio_auxiliar, 2) }}</span>
                                        </div>
                                    </div>
                                </section>

                                <section aria-label="Grupos disponibles">
                                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-umss-gray-700">Grupos disponibles</h4>
                                    @if ($course->groups->isEmpty())
                                        <p class="text-xs text-umss-gray-700 text-center py-2 rounded-lg border border-umss-gray-200 bg-umss-gray-100/60">Sin grupos disponibles</p>
                                    @else
                                        <div class="rounded-lg border border-umss-gray-200 bg-umss-gray-100/60 divide-y divide-umss-gray-200">
                                            @foreach ($course->groups as $group)
                                                @php
                                                    $inscritos = $group->inscritos_count ?? 0;
                                                    $full = $inscritos >= $group->cupo_maximo;
                                                    $isHabilitado = $group->status === \App\Enums\GroupStatus::HABILITADO;
                                                    $cupoMinimo = $group->cupo_minimo ?? \App\Support\BusinessRules::MIN_GROUP_CAPACITY;
                                                @endphp
                                                <div class="flex justify-between items-center gap-3 px-4 py-3 text-xs">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-semibold text-umss-black">{{ $group->nombre }}</span>
                                                            @if ($isHabilitado)
                                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-umss-green-light text-umss-green-dark">Habilitado</span>
                                                            @else
                                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-umss-amber-light text-umss-amber-dark">En preinscripción ({{ $inscritos }}/{{ $cupoMinimo }} cupos mínimos)</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-0.5">
                                                            <span class="text-umss-gray-700">{{ $group->hora_inicio->format('H:i') }} - {{ $group->hora_fin->format('H:i') }}</span>
                                                            <span class="text-umss-gray-700 ml-1 font-mono text-[11px]">({{ $inscritos }}/{{ $group->cupo_maximo }} cupos)</span>
                                                        </div>
                                                    </div>
                                                    @if ($full)
                                                        <span class="text-umss-red font-semibold text-xs px-2 py-0.5 rounded bg-umss-red/10 shrink-0">Lleno</span>
                                                    @else
                                                        <a href="{{ route('preinscripcion', ['group' => $group->id]) }}"
                                                            class="inline-flex items-center shrink-0 text-umss-navy hover:text-umss-navy-dark font-semibold text-xs transition">
                                                            Preinscribirse
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $courses->links() }}
        </div>
    @endif
</div>
