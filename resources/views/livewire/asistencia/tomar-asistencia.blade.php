<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Asistencia</flux:heading>
            <flux:text class="mt-1">Elige el día y la clase para pasar lista</flux:text>
        </div>
    </div>

    @if ($clase)
        {{-- ─────────── Lista de una clase ─────────── --}}
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:button wire:click="volver" icon="arrow-left" variant="ghost" size="sm">Volver al calendario</flux:button>
                <flux:badge color="green" size="lg">{{ $presentes }} / {{ $roster->count() }} presentes</flux:badge>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ $clase->nombre }}</flux:heading>
                <flux:text size="sm" class="mt-0.5 capitalize">
                    {{ $fechaLista }} · {{ substr((string) $clase->hora_inicio, 0, 5) }}
                    · {{ $clase->grupo_etario->etiqueta() }} · {{ $clase->sede?->nombre }}
                </flux:text>
            </div>

            <div class="space-y-2">
                @forelse ($roster as $estudiante)
                    @php($estado = $estados[$estudiante->id] ?? null)
                    <div wire:key="ros-{{ $estudiante->id }}"
                        class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading class="min-w-0 truncate">{{ $estudiante->nombre }}</flux:heading>
                        <div class="flex shrink-0 gap-2">
                            <flux:button size="sm"
                                :variant="$estado === 'presente' ? 'primary' : 'outline'"
                                wire:click="marcar({{ $estudiante->id }}, 'presente')">
                                Presente
                            </flux:button>
                            <flux:button size="sm"
                                :variant="$estado === 'ausente' ? 'danger' : 'outline'"
                                wire:click="marcar({{ $estudiante->id }}, 'ausente')">
                                Ausente
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                        <flux:text>No hay estudiantes activos para esta clase (misma sede y grupo etario).</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        {{-- ─────────── Calendario semanal ─────────── --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <flux:button wire:click="semanaAnterior" icon="chevron-left" variant="ghost" size="sm" aria-label="Semana anterior" />
                <flux:button wire:click="irAHoy" variant="outline" size="sm">Hoy</flux:button>
                <flux:button wire:click="semanaSiguiente" icon="chevron-right" variant="ghost" size="sm" aria-label="Semana siguiente" />
            </div>
            <flux:text class="font-medium capitalize">{{ $rangoSemana }}</flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach ($dias as $dia)
                <div class="flex flex-col rounded-xl border bg-white dark:bg-zinc-800
                    {{ $dia['esHoy'] ? 'border-red-400 ring-1 ring-red-400 dark:border-red-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                    {{-- Encabezado del día --}}
                    <div class="flex items-baseline justify-between border-b px-3 py-2
                        {{ $dia['esHoy'] ? 'border-red-200 dark:border-red-900/50' : 'border-zinc-100 dark:border-zinc-700/60' }}">
                        <span class="text-sm font-semibold capitalize {{ $dia['esHoy'] ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-300' }}">
                            {{ $dia['diaNombre'] }}
                        </span>
                        <span class="text-xs text-zinc-400">{{ $dia['diaNumero'] }} {{ $dia['mes'] }}</span>
                    </div>

                    {{-- Clases del día --}}
                    <div class="flex flex-1 flex-col gap-2 p-2">
                        @forelse ($dia['clases'] as $item)
                            @php($c = $item['clase'])
                            <button type="button" wire:key="cal-{{ $c->id }}-{{ $dia['fecha'] }}"
                                wire:click="abrirClase({{ $c->id }}, '{{ $dia['fecha'] }}')"
                                class="group w-full rounded-lg border border-zinc-200 p-2 text-left transition hover:border-red-300 hover:bg-red-50/50 dark:border-zinc-700 dark:hover:border-red-500/60 dark:hover:bg-red-950/20">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-semibold text-zinc-500">{{ substr((string) $c->hora_inicio, 0, 5) }}</span>
                                    @if ($item['tomada'])
                                        <flux:badge color="green" size="sm">{{ $item['presentes'] }}/{{ $item['esperados'] }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ $item['esperados'] }}</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-1 truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $c->nombre }}</div>
                                <div class="truncate text-xs text-zinc-400">{{ $c->grupo_etario->etiqueta() }}</div>
                            </button>
                        @empty
                            <div class="flex flex-1 items-center justify-center py-6">
                                <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <flux:text size="sm" class="text-zinc-500">
            El número indica los alumnos esperados; una vez tomada la lista se muestra en verde
            como presentes/esperados.
        </flux:text>
    @endif
</div>
