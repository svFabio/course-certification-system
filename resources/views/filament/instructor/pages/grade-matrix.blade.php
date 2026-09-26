<x-filament-panels::page>
    @php
        $visibleCriteria = array_values(array_filter(
            $criteria,
            fn (array $criterion): bool => $criterion['op'] !== 'delete',
        ));
    @endphp

    <div class="space-y-6">
        {{-- Course + Group selectors and staged attendance weight --}}
        <div class="rounded-xl border border-umss-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                <div class="w-full max-w-xs">
                    <label for="course-select" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                        Seleccionar Curso
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="course-select" wire:model.live="selectedCourseId">
                            <option value="">-- Seleccionar un curso --</option>
                            @foreach($courseOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="w-full max-w-xs">
                    <label for="group-select" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                        Seleccionar Grupo
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="group-select" wire:model.live="selectedGroupId">
                            <option value="">-- Seleccionar un grupo --</option>
                            @foreach($groupOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if($selectedCourseId)
                    <div class="w-32">
                        <label for="attendance-weight" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                            Peso Asistencia (%)
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                id="attendance-weight"
                                type="number"
                                min="0"
                                max="100"
                                wire:model.live.debounce.300ms="attendanceWeightInput"
                            />
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>

            @if(count($courseOptions) === 0)
                <p class="mt-3 border-t border-umss-gray-200 pt-3 text-xs text-umss-gray-700">
                    No tiene cursos asignados actualmente. Contacte al administrador.
                </p>
            @endif
        </div>

        {{-- Domain / validation errors --}}
        @if($lastError)
            <div class="rounded-xl border border-umss-red/40 bg-umss-red/10 px-4 py-3 text-sm font-medium text-umss-red">
                {!! nl2br(e($lastError)) !!}
            </div>
        @endif

        {{-- Matrix toolbar --}}
        @if($selectedGroupId)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-4 text-xs text-umss-gray-700">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <x-filament::icon icon="heroicon-o-users" class="h-4 w-4 text-umss-navy" style="width: 16px; height: 16px;" />
                        <span><strong>{{ count($rows) }}</strong> participante(s) inscrito(s)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4 text-umss-navy" style="width: 16px; height: 16px;" />
                        <span><strong>{{ $sessionCount }}</strong> sesión(es) programada(s)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-4 w-4 text-umss-navy" style="width: 16px; height: 16px;" />
                        <span><strong>{{ count($visibleCriteria) }}</strong> evaluación(es)</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <span class="text-xs text-umss-gray-700">
                        Edite libremente (columnas, ponderaciones y notas) y pulse «Guardar cambios» para validar y guardar todo a la vez.
                    </span>

                    @if($this->hasUnsavedChanges())
                        <span class="inline-flex items-center rounded-full border border-umss-amber bg-umss-amber-light px-2.5 py-1 text-[11px] font-bold text-umss-amber-dark">
                            Cambios sin guardar
                        </span>
                        <x-filament::button wire:click="saveAll" size="sm" color="primary" icon="heroicon-o-check">
                            Guardar cambios
                        </x-filament::button>
                        <x-filament::button wire:click="discardChanges" size="sm" color="gray" icon="heroicon-o-arrow-uturn-left">
                            Descartar
                        </x-filament::button>
                    @endif

                    <x-filament::button wire:click="openCreateCriteria" size="sm" icon="heroicon-o-plus">
                        Nueva evaluación
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- No courses --}}
        @if(count($courseOptions) === 0)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-gray-100 text-umss-navy mb-4">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Sin Cursos Asignados</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    No tiene cursos asignados actualmente. Contacte al administrador.
                </p>
            </div>

        {{-- No group selected --}}
        @elseif(! $selectedGroupId)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-gray-100 text-umss-navy mb-4">
                    <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Seleccione un Grupo</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    Elija un curso y un grupo en los menús superiores para cargar la matriz de calificaciones.
                </p>
            </div>

        {{-- Group selected, no students --}}
        @elseif(count($rows) === 0)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-gray-100 text-umss-navy mb-4">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Sin Participantes</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    No hay participantes inscritos en este grupo.
                </p>
            </div>

        {{-- Group selected, no evaluation criteria --}}
        @elseif(count($criteria) === 0)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-amber-light text-umss-amber-dark mb-4">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Sin Evaluaciones</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    Este curso aún no tiene criterios de evaluación definidos.
                </p>
                <div class="mt-4">
                    <x-filament::button wire:click="openCreateCriteria" icon="heroicon-o-plus" color="success">
                        Agregar primera evaluación
                    </x-filament::button>
                </div>
            </div>

        {{-- Grade matrix --}}
        @else
            <div class="overflow-hidden rounded-xl border border-umss-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-umss-gray-200 bg-umss-gray-100">
                                <th class="whitespace-nowrap px-3 py-3 font-bold text-umss-navy uppercase tracking-wider min-w-[220px]">
                                    Participante
                                </th>
                                <th class="whitespace-nowrap px-3 py-3 text-center font-bold text-umss-navy uppercase tracking-wider min-w-[130px] border-l border-umss-gray-200">
                                    <div class="text-[11px] font-bold normal-case tracking-normal">
                                        Asistencia
                                    </div>
                                    <div class="text-[10px] font-normal text-umss-gray-700 normal-case tracking-normal">
                                        {{ $this->stagedWeightLabel() }}
                                    </div>
                                </th>
                                @foreach($visibleCriteria as $criterion)
                                    <th class="whitespace-nowrap px-2 py-2.5 text-center font-bold text-umss-navy uppercase tracking-wider border-l border-umss-gray-200 min-w-[120px]">
                                        <div class="text-[11px] font-bold normal-case tracking-normal">
                                            {{ $criterion['nombre'] }}
                                        </div>
                                        <div class="text-[10px] font-normal text-umss-gray-700 normal-case tracking-normal">
                                            {{ $criterion['ponderacion_label'] }}
                                        </div>
                                        <div class="mt-1 flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="openEditCriteria({{ $criterion['id'] }})"
                                                title="Editar evaluación"
                                                class="rounded p-1 text-umss-navy transition hover:bg-umss-gray-200"
                                            >
                                                <x-filament::icon icon="heroicon-o-pencil-square" class="h-3.5 w-3.5" style="width: 14px; height: 14px;" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeCriteria({{ $criterion['id'] }})"
                                                wire:confirm="¿Quitar esta evaluación de la matriz? El cambio se aplicará al guardar."
                                                title="Eliminar evaluación"
                                                class="rounded p-1 text-umss-red transition hover:bg-umss-red/10"
                                            >
                                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" style="width: 14px; height: 14px;" />
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="whitespace-nowrap px-3 py-3 text-center font-bold text-umss-navy uppercase tracking-wider border-l border-umss-gray-200 min-w-[110px]">
                                    Nota Final
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-umss-gray-200">
                            @foreach($rows as $row)
                                <tr class="transition hover:bg-umss-gray-100/60">
                                    <td class="px-3 py-2">
                                        <div class="font-semibold text-umss-black">{{ $row['full_name'] }}</div>
                                        <div class="font-mono text-[11px] font-medium text-umss-gray-700">{{ $row['ci'] }}</div>
                                    </td>
                                    <td class="px-2 py-2 text-center border-l border-umss-gray-200">
                                        <span class="inline-flex items-center rounded-full border border-umss-sky/30 bg-umss-sky-light px-2.5 py-1 text-[11px] font-bold text-umss-sky-dark">{{ number_format($row['attendance_score'], 2) }} <span class="font-medium text-umss-gray-700">({{ $row['attendance_attended'] }}/{{ $sessionCount }})</span></span>
                                    </td>
                                    @foreach($visibleCriteria as $criterion)
                                        @php
                                            $notaKey = $row['id'].':'.$criterion['id'];
                                            $notaValue = $pendingNotas[$notaKey] ?? $row['notas'][$criterion['id']] ?? '';
                                        @endphp
                                        <td class="px-1.5 py-1.5 text-center border-l border-umss-gray-200">
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                wire:change="stageNota({{ $row['id'] }}, {{ $criterion['id'] }}, $event.target.value)"
                                                value="{{ $notaValue }}"
                                                class="w-20 rounded-md border border-umss-gray-300 bg-white px-1.5 py-1 text-center text-xs font-semibold text-umss-black transition focus:outline-none focus:ring-1 focus:ring-umss-navy"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center border-l border-umss-gray-200">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold
                                            {{ $row['passed'] ? 'bg-umss-green-light text-umss-green-dark' : 'bg-umss-red/10 text-umss-red' }}">
                                            {{ number_format($row['final_grade'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-umss-gray-200 bg-umss-gray-100/50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-umss-gray-700 font-medium">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-sky inline-block"></span> Asistencia (lectura)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-green inline-block"></span> Nota final ≥ 70
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-red inline-block"></span> Nota final &lt; 70
                        </span>
                    </div>
                    <span class="text-xs text-umss-gray-700">
                        Rango de notas permitido: 0 a 100.
                    </span>
                </div>
            </div>
        @endif
    </div>

    {{-- Unsaved-changes switch guard --}}
    @if($switchConfirmOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-umss-black/50" wire:click="cancelSwitch"></div>
            <div class="relative w-full max-w-md rounded-xl border border-umss-gray-200 bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-umss-navy">Cambios sin guardar</h3>
                <p class="mt-3 text-sm text-umss-gray-700">
                    Tiene cambios sin guardar. Si cambia de selección se descartarán.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button color="gray" wire:click="cancelSwitch">
                        Seguir aquí
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmSwitch" icon="heroicon-o-trash">
                        Continuar y descartar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Criteria create/edit modal --}}
    @if($criteriaModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-umss-black/50" wire:click="closeCriteriaModal"></div>
            <div class="relative w-full max-w-md rounded-xl border border-umss-gray-200 bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-umss-navy">
                    {{ $editingCriteriaId === null ? 'Nueva Evaluación' : 'Editar Evaluación' }}
                </h3>

                <div class="mt-4 space-y-4">
                    @if($criteriaError)
                        <div class="rounded-lg border border-umss-red/40 bg-umss-red/10 px-3 py-2 text-xs font-medium text-umss-red">
                            {{ $criteriaError }}
                        </div>
                    @endif

                    <div>
                        <label for="criteria-nombre" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                            Nombre
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input id="criteria-nombre" type="text" wire:model="criteriaNombre" />
                        </x-filament::input.wrapper>
                        @error('criteriaNombre')
                            <p class="mt-1 text-xs text-umss-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="criteria-ponderacion" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                            Ponderación (%)
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                id="criteria-ponderacion"
                                type="number"
                                min="1"
                                max="100"
                                wire:model="criteriaPonderacion"
                            />
                        </x-filament::input.wrapper>
                        @error('criteriaPonderacion')
                            <p class="mt-1 text-xs text-umss-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-xs text-umss-gray-700">
                        La suma de esta ponderación con el resto se validará al guardar los cambios.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button color="gray" wire:click="closeCriteriaModal">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button wire:click="saveCriteria" icon="heroicon-o-check">
                        Guardar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
