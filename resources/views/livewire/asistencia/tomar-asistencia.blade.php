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
                <flux:text size="sm" class="mt-0.5 first-letter:uppercase">
                    {{ $fechaLista }}@if ($horarioLista) · {{ $horarioLista }}@endif
                    · {{ $clase->grupo_etario->etiqueta() }} · {{ $clase->sede?->nombre }}
                </flux:text>
            </div>

            <div class="space-y-2">
                @forelse ($roster as $matricula)
                    @php($estado = $estados[$matricula->id] ?? null)
                    <div wire:key="ros-{{ $matricula->id }}"
                        class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading class="min-w-0 truncate">{{ $matricula->persona->nombreCompleto() }}</flux:heading>
                        <div class="flex shrink-0 gap-2">
                            <flux:button size="sm"
                                :variant="$estado === 'presente' ? 'primary' : 'outline'"
                                wire:click="marcar({{ $matricula->id }}, 'presente')">
                                Presente
                            </flux:button>
                            <flux:button size="sm"
                                :variant="$estado === 'ausente' ? 'danger' : 'outline'"
                                wire:click="marcar({{ $matricula->id }}, 'ausente')">
                                Ausente
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                        <flux:text>No hay alumnos con matrícula activa para esta clase (misma sede y grupo etario).</flux:text>
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
            <flux:text class="font-medium first-letter:uppercase">{{ $rangoSemana }}</flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach ($dias as $dia)
                <div class="flex flex-col overflow-hidden rounded-xl border bg-white dark:bg-zinc-800
                    {{ $dia['esHoy'] ? 'border-red-300 shadow-sm dark:border-red-500/60' : 'border-zinc-200 dark:border-zinc-700' }}">
                    {{-- Encabezado del día --}}
                    <div class="flex items-center justify-between gap-2 px-3 py-2.5
                        {{ $dia['esHoy'] ? 'bg-red-50 dark:bg-red-950/30' : 'bg-zinc-50 dark:bg-zinc-900/40' }}">
                        <span class="text-sm font-semibold capitalize {{ $dia['esHoy'] ? 'text-red-700 dark:text-red-300' : 'text-zinc-600 dark:text-zinc-300' }}">
                            {{ $dia['diaNombre'] }}
                        </span>
                        <span class="flex h-7 min-w-[1.75rem] items-center justify-center rounded-full px-1.5 text-sm font-semibold
                            {{ $dia['esHoy'] ? 'bg-red-600 text-white' : 'text-zinc-400' }}">
                            {{ $dia['diaNumero'] }}
                        </span>
                    </div>

                    {{-- Clases del día --}}
                    <div class="flex flex-1 flex-col gap-2 p-2">
                        @forelse ($dia['clases'] as $item)
                            @php($c = $item['clase'])
                            <button type="button" wire:key="cal-{{ $c->id }}-{{ $dia['fecha'] }}-{{ $item['horaInicio'] }}"
                                wire:click="abrirClase({{ $c->id }}, '{{ $dia['fecha'] }}')"
                                class="group w-full rounded-lg border border-zinc-200 bg-white p-2.5 text-left shadow-sm transition hover:-translate-y-px hover:border-red-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-red-500/60">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                                        <flux:icon.clock variant="micro" class="text-zinc-400" />
                                        {{ $item['horaInicio'] }}–{{ $item['horaFin'] }}
                                    </span>
                                    @if ($item['tomada'])
                                        <flux:badge color="green" size="sm" icon="check">{{ $item['presentes'] }}/{{ $item['esperados'] }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ $item['esperados'] }}</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-1.5 truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $c->nombre }}</div>
                                <div class="mt-0.5 truncate text-xs text-zinc-400">
                                    {{ $c->grupo_etario->etiqueta() }}@if ($item['titular']) · {{ $item['titular'] }}@endif
                                </div>
                            </button>
                        @empty
                            <div class="flex flex-1 items-center justify-center py-8">
                                <span class="text-xs text-zinc-300 dark:text-zinc-600">Sin clases</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
            <span class="inline-flex items-center gap-1.5">
                <flux:badge color="zinc" size="sm">N</flux:badge> alumnos esperados
            </span>
            <span class="inline-flex items-center gap-1.5">
                <flux:badge color="green" size="sm" icon="check">N</flux:badge> lista ya tomada (presentes/esperados)
            </span>
        </div>
    @endif
</div>
