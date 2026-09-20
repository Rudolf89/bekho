<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Tarifas por sede</flux:heading>
        <flux:text class="mt-1">Cada sede define sus valores de matrícula y mensualidad, por tramo familiar. Sin tarifas no se generan los cargos de la sede.</flux:text>
    </div>

    @forelse ($sedes as $sede)
        <div wire:key="sede-{{ $sede->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $sede->nombre }}</flux:heading>
                    @if ($sede->comuna)
                        <flux:text size="sm" class="mt-0.5">{{ $sede->comuna }}</flux:text>
                    @endif
                </div>
                @unless (auth()->user()?->esSoloLectura())
                    <flux:button wire:click="abrirNueva({{ $sede->id }})" size="sm" variant="primary" icon="plus">Agregar tarifa</flux:button>
                @endunless
            </div>

            @if ($sede->tarifas->isEmpty())
                <flux:callout icon="exclamation-triangle" color="amber" class="mt-4">
                    Esta sede no tiene tarifas cargadas: no se pueden generar sus cargos.
                </flux:callout>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                <th class="py-2 pr-3">Tipo de cargo</th>
                                <th class="py-2 pr-3">Tramo (alumnos)</th>
                                <th class="py-2 pr-3">Monto por alumno</th>
                                <th class="py-2 pr-3">Vigente desde</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sede->tarifas->sortBy([['tipo_cargo_id', 'asc'], ['cantidad_alumnos', 'asc']]) as $tarifa)
                                <tr wire:key="tarifa-{{ $tarifa->id }}" class="border-b border-zinc-100 dark:border-zinc-700/60">
                                    <td class="py-2 pr-3 font-medium">{{ $tarifa->tipoCargo?->nombre }}</td>
                                    <td class="py-2 pr-3 tabular-nums">{{ $tarifa->cantidad_alumnos }}+</td>
                                    <td class="py-2 pr-3 tabular-nums">${{ number_format($tarifa->monto_por_alumno, 0, ',', '.') }}</td>
                                    <td class="py-2 pr-3">{{ $tarifa->vigente_desde?->format('d-m-Y') ?? '—' }}</td>
                                    <td class="text-right">
                                        @unless (auth()->user()?->esSoloLectura())
                                            <button type="button" wire:click="eliminarTarifa({{ $tarifa->id }})" class="text-zinc-400 hover:text-red-500" title="Eliminar">&times;</button>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>No tienes sedes que administrar.</flux:text>
        </div>
    @endforelse

    {{-- Modal nueva tarifa --}}
    <flux:modal name="tarifa-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardarTarifa" class="space-y-5">
            <flux:heading size="lg">Agregar tarifa</flux:heading>
            <flux:text>El tramo es la cantidad de hermanos matriculados EN ESTA SEDE a partir de la cual aplica el monto.</flux:text>

            <flux:select wire:model="tarifaTipoId" label="Tipo de cargo" placeholder="Selecciona">
                @foreach ($tipos as $t)
                    <flux:select.option value="{{ $t->id }}">{{ $t->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="tarifaCantidad" type="number" label="Tramo (alumnos)" required />
                <flux:input wire:model="tarifaMonto" type="number" label="Monto por alumno (CLP)" required />
                <flux:input wire:model="tarifaVigenteDesde" type="date" label="Vigente desde (opcional)" />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
