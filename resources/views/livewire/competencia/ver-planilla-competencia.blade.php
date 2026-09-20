@php($editable = $planilla->estado === \App\Enums\EstadoPlanillaCompetencia::Borrador && ! auth()->user()?->esSoloLectura())
<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:button :href="route('competencia.index')" wire:navigate size="sm" variant="ghost" icon="arrow-left">Volver</flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">{{ $planilla->prueba?->nombre }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $planilla->categoria?->nombre ?? 'Sin categoría' }}
                    @if ($planilla->grupoEdad) · {{ $planilla->grupoEdad->nombre }} @endif
                    @if ($planilla->genero) · {{ $planilla->genero }} @endif
                    @if ($planilla->fecha) · {{ $planilla->fecha->format('d-m-Y') }} @endif
                    @if ($planilla->nro_pista) · Pista {{ $planilla->nro_pista }} @endif
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:badge :color="$planilla->estado->color()" size="lg">{{ $planilla->estado->etiqueta() }}</flux:badge>
                @if (! auth()->user()?->esSoloLectura())
                    @if ($planilla->estado === \App\Enums\EstadoPlanillaCompetencia::Borrador)
                        <flux:button wire:click="cerrar" size="sm" variant="primary" icon="lock-closed">Cerrar</flux:button>
                    @else
                        <flux:button wire:click="reabrir" size="sm" variant="ghost" icon="lock-open">Reabrir</flux:button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Jueces --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-3">Jueces</flux:heading>
        <div class="flex flex-wrap gap-2">
            @forelse ($planilla->jueces as $juez)
                <span wire:key="juez-{{ $juez->id }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-sm dark:bg-zinc-700">
                    <span class="font-semibold">{{ $juez->papel->etiqueta() }}:</span> {{ $juez->nombre }}
                    @if ($juez->nivel_pais) <span class="text-zinc-500">({{ $juez->nivel_pais }})</span> @endif
                    @if ($editable)
                        <button type="button" wire:click="eliminarJuez({{ $juez->id }})" class="text-zinc-400 hover:text-red-500">&times;</button>
                    @endif
                </span>
            @empty
                <flux:text class="text-zinc-500">Sin jueces asignados.</flux:text>
            @endforelse
        </div>

        @if ($editable)
            <form wire:submit="agregarJuez" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:select wire:model="juezPapel" label="Papel" class="sm:w-40">
                    @foreach ($papeles as $p)
                        <flux:select.option value="{{ $p->value }}">{{ $p->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="juezNombre" label="Nombre" class="flex-1" />
                <flux:input wire:model="juezNivel" label="Nivel/País" class="sm:w-40" />
                <flux:button type="submit" variant="primary" icon="plus">Agregar</flux:button>
            </form>
            @error('juezNombre') <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text> @enderror
        @endif
    </div>

    {{-- Competidores y puntajes --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-3">Competidores y puntajes</flux:heading>

        @if ($criterios->isEmpty())
            <flux:callout icon="information-circle">Esta prueba no tiene criterios definidos.</flux:callout>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="py-2 pr-3">#</th>
                            <th class="py-2 pr-3">Competidor</th>
                            @foreach ($criterios as $crit)
                                <th class="px-2 py-2 text-center" title="{{ $crit->papel_juez->etiqueta() }}">
                                    <div class="whitespace-nowrap">{{ $crit->nombre }}</div>
                                    <div class="text-xs font-normal text-zinc-400">{{ $crit->papel_juez->etiqueta() }}</div>
                                </th>
                            @endforeach
                            <th class="px-2 py-2 text-center">Total</th>
                            @if ($editable) <th></th> @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ranking as $i => $competidor)
                            <tr wire:key="comp-{{ $competidor->id }}" class="border-b border-zinc-100 dark:border-zinc-700/60">
                                <td class="py-2 pr-3 text-zinc-500">{{ $i + 1 }}</td>
                                <td class="py-2 pr-3">
                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $competidor->nombre }}</div>
                                    <div class="text-xs text-zinc-400">
                                        {{ $competidor->edad ? $competidor->edad.' años' : '' }}{{ $competidor->pais ? ' · '.$competidor->pais : '' }}
                                    </div>
                                </td>
                                @foreach ($criterios as $crit)
                                    <td class="px-2 py-2 text-center">
                                        <input type="number" min="0" max="9"
                                            wire:model="puntajes.{{ $competidor->id }}.{{ $crit->id }}"
                                            wire:change="guardarPuntaje({{ $competidor->id }}, {{ $crit->id }})"
                                            @disabled(! $editable)
                                            class="w-12 rounded border border-zinc-300 bg-white px-1 py-1 text-center tabular-nums disabled:opacity-60 dark:border-zinc-600 dark:bg-zinc-900" />
                                    </td>
                                @endforeach
                                <td class="px-2 py-2 text-center font-bold tabular-nums">{{ $competidor->total }}</td>
                                @if ($editable)
                                    <td class="text-right">
                                        <button type="button" wire:click="eliminarCompetidor({{ $competidor->id }})" class="text-zinc-400 hover:text-red-500" title="Eliminar">&times;</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $criterios->count() + 3 }}" class="py-6 text-center text-zinc-500">Aún no hay competidores.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <flux:text size="sm" class="mt-2 text-zinc-500">El dígito 1–9 equivale a 9.1–9.9. El 0 es penalización (solo donde el criterio lo permite).</flux:text>
        @endif

        @if ($editable)
            <form wire:submit="agregarCompetidor" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:input wire:model="compNombre" label="Nombre del competidor" class="flex-1" />
                <flux:input wire:model="compEdad" type="number" label="Edad" class="sm:w-24" />
                <flux:input wire:model="compPais" label="País" class="sm:w-32" />
                <flux:button type="submit" variant="primary" icon="plus">Agregar</flux:button>
            </form>
            @error('compNombre') <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text> @enderror
        @endif
    </div>
</div>
