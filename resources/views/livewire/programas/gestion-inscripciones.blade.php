<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Inscripciones a programas</flux:heading>
        <flux:text class="mt-1">Inscribe personas, registra horas y verifica requisitos. El ascenso lo aprueba el instructor del alumno o la dirección.</flux:text>
    </div>

    {{-- Nueva inscripción --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="crearInscripcion" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <flux:select wire:model="nuevaPersonaId" label="Persona" placeholder="Elige una persona…">
                @foreach ($personas as $persona)
                    <flux:select.option value="{{ $persona->id }}">{{ $persona->nombreCompleto() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="nuevoProgramaId" label="Programa" placeholder="Elige un programa…">
                @foreach ($programas as $programa)
                    <flux:select.option value="{{ $programa->id }}">{{ $programa->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary" icon="plus">Inscribir</flux:button>
        </form>
    </div>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
        {{-- Listado --}}
        <div class="w-full space-y-3 lg:w-1/2">
            <x-tabla.buscador />
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Persona</flux:table.column>
                        <flux:table.column>Programa / etapa</flux:table.column>
                        <flux:table.column>Horas</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($inscripciones as $insc)
                            <flux:table.row wire:key="insc-{{ $insc->id }}" class="cursor-pointer" wire:click="$set('inscripcionId', {{ $insc->id }})">
                                <flux:table.cell>{{ $insc->persona?->nombreCompleto() }}</flux:table.cell>
                                <flux:table.cell>
                                    {{ $insc->programa?->nombre }}
                                    <span class="block text-xs text-zinc-500">{{ $insc->etapaActual?->nombre ?? '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell>{{ (int) $insc->horas_total }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="3">Sin inscripciones.</flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Detalle --}}
        <div class="w-full lg:w-1/2">
            @if ($inscripcion)
                <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">{{ $inscripcion->persona?->nombreCompleto() }}</flux:heading>
                            <flux:text>{{ $inscripcion->programa?->nombre }} · {{ $inscripcion->etapaActual?->nombre }}</flux:text>
                        </div>
                        <flux:badge :color="$inscripcion->estado->color()">{{ $inscripcion->estado->etiqueta() }}</flux:badge>
                    </div>

                    @php($req = (int) ($inscripcion->etapaActual?->horas_requeridas ?? 0))
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>Horas: {{ $inscripcion->horasAcumuladas() }}{{ $req ? ' / '.$req : '' }}</span>
                        </div>
                        @if ($req)
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, (int) round($inscripcion->horasAcumuladas() / $req * 100)) }}%"></div>
                            </div>
                        @endif
                    </div>

                    {{-- Alta de horas --}}
                    <form wire:submit="agregarHora" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                        <flux:input type="date" wire:model="horaFecha" label="Fecha" />
                        <flux:input type="number" step="0.5" wire:model="horaCantidad" label="Horas" class="sm:w-24" />
                        <flux:button type="submit" size="sm" icon="plus">Agregar</flux:button>
                    </form>

                    @if ($inscripcion->horas->isNotEmpty())
                        <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                            @foreach ($inscripcion->horas as $hora)
                                <li class="flex items-center justify-between py-1.5">
                                    <span>{{ $hora->fecha?->format('d-m-Y') }} · {{ $hora->horas }} h</span>
                                    <flux:button variant="ghost" size="xs" icon="trash" wire:click="eliminarHora({{ $hora->id }})" />
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Requisitos --}}
                    @if ($inscripcion->etapaActual?->requisitos->isNotEmpty())
                        <div>
                            <flux:text class="text-xs font-semibold uppercase text-zinc-500">Requisitos</flux:text>
                            <ul class="mt-1 space-y-1">
                                @foreach ($inscripcion->etapaActual->requisitos as $requisito)
                                    <li class="flex items-start gap-2 text-sm">
                                        @if ($requisito->esAutomatico())
                                            <flux:icon.academic-cap variant="micro" class="mt-0.5 text-sky-500" />
                                            <span>{{ $requisito->descripcion }} <span class="text-xs text-zinc-500">(prueba escrita)</span></span>
                                        @else
                                            <button type="button" wire:click="alternarRequisito({{ $requisito->id }})" class="mt-0.5">
                                                @if ($inscripcion->cumpleRequisito($requisito))
                                                    <flux:icon.check-circle variant="micro" class="text-emerald-500" />
                                                @else
                                                    <flux:icon.minus-circle variant="micro" class="text-zinc-400" />
                                                @endif
                                            </button>
                                            <span>{{ $requisito->descripcion }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($puedeAprobarActual)
                        <flux:button variant="primary" icon="check" wire:click="aprobar" class="w-full">Aprobar ascenso</flux:button>
                    @endif
                </div>
            @else
                <flux:text class="text-zinc-500">Elige una inscripción para ver su detalle.</flux:text>
            @endif
        </div>
    </div>
</div>
