<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Group Selector --}}
        <div class="rounded-xl border border-umss-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="w-full max-w-xl">
                    <label for="group-select" class="block text-xs font-bold uppercase tracking-wider text-umss-navy mb-1.5">
                        Seleccionar Grupo de Capacitación
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="group-select" wire:model.live="selectedGroupId">
                            <option value="">-- Seleccionar un grupo --</option>
                            @foreach($this->groupOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if($selectedGroupId && count($students) > 0 && count($sessions) > 0)
                    <div class="flex items-center gap-3">
                        <x-filament::button wire:click="save" size="md" icon="heroicon-o-check" color="success">
                            Guardar Asistencia
                        </x-filament::button>
                    </div>
                @endif
            </div>

            @if($selectedGroupId)
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-umss-gray-700 border-t border-umss-gray-200 pt-3">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <x-filament::icon icon="heroicon-o-users" class="h-4 w-4 text-umss-navy" style="width: 16px; height: 16px;" />
                        <span><strong>{{ count($students) }}</strong> participante(s) inscrito(s)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4 text-umss-navy" style="width: 16px; height: 16px;" />
                        <span><strong>{{ count($sessions) }}</strong> sesión(es) programada(s)</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- No group selected --}}
        @if(! $selectedGroupId)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-gray-100 text-umss-navy mb-4">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Seleccione un Grupo</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    Elija un grupo en el menú superior para cargar la planilla de asistencia.
                </p>
            </div>

        {{-- Group selected, no sessions --}}
        @elseif(count($sessions) === 0)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-amber-light text-umss-amber-dark mb-4">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Sin Sesiones Programadas</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    Este grupo no tiene fechas ni clases creadas en el calendario académico.
                </p>
            </div>

        {{-- Group selected, no students --}}
        @elseif(count($students) === 0)
            <div class="rounded-xl border border-umss-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-umss-gray-100 text-umss-gray-700 mb-4">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-8 w-8" style="width: 32px; height: 32px;" />
                </div>
                <h3 class="text-base font-semibold text-umss-navy">Sin Estudiantes Confirmados</h3>
                <p class="mt-1 text-sm text-umss-gray-700">
                    No hay participantes en estado <strong>Inscrito</strong> para este grupo actualmente.
                </p>
            </div>

        {{-- Attendance Grid --}}
        @else
            <div class="overflow-hidden rounded-xl border border-umss-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-umss-gray-200 bg-umss-gray-100">
                                <th class="whitespace-nowrap px-3 py-3 font-bold text-umss-navy uppercase tracking-wider w-10 text-center">
                                    #
                                </th>
                                <th class="whitespace-nowrap px-3 py-3 font-bold text-umss-navy uppercase tracking-wider w-24">
                                    C.I.
                                </th>
                                <th class="whitespace-nowrap px-3 py-3 font-bold text-umss-navy uppercase tracking-wider min-w-[200px]">
                                    Estudiante
                                </th>
                                @foreach($sessions as $session)
                                    <th class="whitespace-nowrap px-2 py-2.5 text-center font-bold text-umss-navy uppercase tracking-wider border-l border-umss-gray-200 min-w-[95px]">
                                        <div class="text-[11px] font-bold">Día {{ $loop->iteration }}</div>
                                        <div class="text-[10px] font-normal text-umss-gray-700 lowercase tracking-normal">
                                            {{ $session['fecha'] }}
                                        </div>
                                        <div class="mt-1">
                                            <button
                                                type="button"
                                                wire:click="markAllForSession({{ $session['id'] }}, 'presente')"
                                                class="inline-block rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-umss-green-light text-umss-green-dark border border-umss-green/30 hover:bg-umss-green hover:text-white transition"
                                                title="Marcar todos Presentes en esta sesión"
                                            >
                                                Todos P
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-umss-gray-200">
                            @foreach($students as $student)
                                <tr class="transition hover:bg-umss-gray-100/60">
                                    <td class="whitespace-nowrap px-3 py-2 text-center font-medium text-umss-gray-700">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 font-mono font-medium text-umss-gray-700">
                                        {{ $student['ci'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 font-semibold text-umss-black">
                                        {{ $student['full_name'] }}
                                    </td>
                                    @foreach($sessions as $session)
                                        @php
                                            $key = "{$student['id']}_{$session['id']}";
                                            $currentStatus = $attendanceData[$key] ?? null;
                                        @endphp
                                        <td class="px-1.5 py-1.5 text-center border-l border-umss-gray-200">
                                            <select
                                                wire:change="markAttendance('{{ $key }}', $event.target.value)"
                                                class="w-full rounded-md border px-1.5 py-1 text-center text-xs font-semibold transition focus:outline-none focus:ring-1 focus:ring-umss-navy
                                                    @if($currentStatus === 'presente') border-umss-green/40 bg-umss-green-light text-umss-green-dark
                                                    @elseif($currentStatus === 'ausente') border-umss-red/40 bg-umss-red/10 text-umss-red
                                                    @elseif($currentStatus === 'justificado') border-umss-amber/40 bg-umss-amber-light text-umss-amber-dark
                                                    @else border-umss-gray-300 bg-white text-umss-gray-700
                                                    @endif"
                                            >
                                                <option value="">--</option>
                                                @foreach($this->statusOptions as $val => $label)
                                                    <option
                                                        value="{{ $val }}"
                                                        {{ $currentStatus === $val ? 'selected' : '' }}
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Table Footer / Save Actions --}}
                <div class="flex items-center justify-between border-t border-umss-gray-200 bg-umss-gray-100/50 px-4 py-3">
                    <div class="flex items-center gap-3 text-xs text-umss-gray-700 font-medium">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-green inline-block"></span> Presente
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-red inline-block"></span> Ausente
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-umss-amber inline-block"></span> Justificado
                        </span>
                    </div>

                    <x-filament::button wire:click="save" size="md" icon="heroicon-o-check" color="success">
                        Guardar Asistencia
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
