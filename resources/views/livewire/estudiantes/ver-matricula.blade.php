@php($persona = $matricula->persona)
<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button :href="route('estudiantes.index')" wire:navigate size="sm" variant="ghost" icon="arrow-left">Volver</flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">{{ $persona?->nombreCompleto() }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $matricula->grupo_etario?->etiqueta() }}
                    @if ($persona?->fecha_nacimiento) · {{ $persona->edad() }} años @endif
                    @if ($matricula->sede) · {{ $matricula->sede->nombre }} @endif
                </flux:text>
            </div>
            <flux:badge :color="$moroso ? 'amber' : 'green'" size="lg">
                {{ $moroso ? 'Pago pendiente' : 'Al día' }}
            </flux:badge>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Columna principal --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Progreso --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">Progreso al siguiente cinturón</flux:heading>
                <div class="flex items-center gap-3">
                    <flux:badge size="sm">{{ $gradoActual?->nombre ?? 'Sin grado' }}</flux:badge>
                    <flux:icon.arrow-right class="size-4 text-zinc-400" />
                    <flux:badge size="sm" color="zinc">{{ $gradoSiguiente?->nombre ?? '—' }}</flux:badge>
                </div>
                @if ($progreso !== null)
                    <div class="mt-4">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full rounded-full bg-[#b01e28]" style="width: {{ $progreso }}%"></div>
                        </div>
                        <flux:text size="sm" class="mt-1">{{ $mesesEnGrado }} de {{ $mesesSugeridos }} meses sugeridos · {{ $progreso }}%</flux:text>
                    </div>
                @else
                    <flux:text size="sm" class="mt-3 text-zinc-500">{{ $mesesEnGrado }} meses en el grado actual.</flux:text>
                @endif
            </div>

            {{-- Requisitos técnicos (del siguiente grado) --}}
            @if ($tecnicasSiguiente->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-3">Requisitos técnicos · {{ $gradoSiguiente?->nombre }}</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tecnicasSiguiente as $tecnica)
                            <flux:badge wire:key="tec-{{ $tecnica->id }}" size="sm" color="zinc">{{ $tecnica->nombre }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Historial de exámenes --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">Historial de exámenes</flux:heading>
                @if ($graduaciones->isEmpty())
                    <flux:text class="text-zinc-500">Todavía no rinde exámenes.</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                    <th class="py-2 pr-3">Fecha</th>
                                    <th class="py-2 pr-3">Grado</th>
                                    <th class="py-2 pr-3">Nota</th>
                                    <th class="py-2 pr-3">Resultado</th>
                                    <th class="py-2 pr-3">Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($graduaciones as $g)
                                    <tr wire:key="grad-{{ $g->id }}" class="border-b border-zinc-100 dark:border-zinc-700/60">
                                        <td class="py-2 pr-3">{{ $g->fecha?->format('d-m-Y') }}</td>
                                        <td class="py-2 pr-3">{{ $g->gradoDestino?->nombre ?? '—' }}</td>
                                        <td class="py-2 pr-3 tabular-nums">{{ $g->nota ?? '—' }}</td>
                                        <td class="py-2 pr-3">{{ $g->resultado?->etiqueta() ?? '—' }}</td>
                                        <td class="py-2 pr-3">{{ $g->fecha_entrega?->format('d-m-Y') ?? ($g->plazoEntregaVencido() ? 'Vencida' : 'Pendiente') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Notas del instructor --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">Notas del instructor</flux:heading>

                @if ($puedeEditar)
                    <form wire:submit="agregarNota" class="mb-4 space-y-2">
                        <flux:textarea wire:model="nuevaNota" rows="2" placeholder="Observación sobre el alumno…" />
                        @error('nuevaNota') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
                        <div class="flex justify-end">
                            <flux:button type="submit" size="sm" variant="primary" icon="plus">Guardar nota</flux:button>
                        </div>
                    </form>
                @endif

                @forelse ($matricula->notas as $nota)
                    <div wire:key="nota-{{ $nota->id }}" class="border-t border-zinc-100 py-3 first:border-t-0 dark:border-zinc-700/60">
                        <flux:text>{{ $nota->cuerpo }}</flux:text>
                        <flux:text size="xs" class="mt-1 text-zinc-400">
                            {{ $nota->autor?->name ?? 'Sistema' }} · {{ $nota->created_at?->format('d-m-Y') }}
                        </flux:text>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">Sin notas registradas.</flux:text>
                @endforelse
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-6">
            {{-- Asistencia --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-2">Asistencia del ciclo</flux:heading>
                <div class="text-3xl font-bold tabular-nums">{{ $asistencia !== null ? $asistencia.'%' : '—' }}</div>
                <flux:text size="sm" class="text-zinc-500">desde el grado actual</flux:text>
            </div>

            {{-- Estado de cuenta --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-2">Estado de cuenta</flux:heading>
                @if ($bloqueado)
                    <flux:callout icon="lock-closed" color="red" class="mb-3">
                        Bloqueado por deuda: superó las 3 clases de gracia. No puede ingresar hasta regularizar.
                    </flux:callout>
                @endif
                @if ($cargosPendientes->isEmpty())
                    <flux:text class="text-zinc-500">Sin cargos pendientes.</flux:text>
                @else
                    <flux:text size="sm">Deuda: <span class="font-semibold">${{ number_format($deuda, 0, ',', '.') }}</span></flux:text>
                    <div class="mt-2 space-y-1">
                        @foreach ($cargosPendientes as $cargo)
                            <div wire:key="cargo-{{ $cargo->id }}" class="flex justify-between text-sm">
                                <span class="text-zinc-500">{{ $cargo->periodo?->format('m-Y') ?? 'Cargo' }}</span>
                                <span class="tabular-nums">${{ number_format($cargo->monto, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
