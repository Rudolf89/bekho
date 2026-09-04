<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Exámenes de grado</flux:heading>
            <flux:text class="mt-1">Convocatorias de examen</flux:text>
        </div>
        <div class="flex gap-2">
            @can('gestionar examenes')
                <flux:button :href="route('examenes.conteo')" icon="trophy" variant="ghost" wire:navigate>Conteo de graduaciones</flux:button>
                <flux:button wire:click="nueva" icon="plus" variant="primary">Nueva convocatoria</flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar por convocatoria o sede…" />
        <x-tabla.resumen
            :total="$convocatorias->count()"
            etiqueta="convocatoria"
            :sumas="[['etiqueta' => 'Inscritos', 'valor' => number_format($totalInscritos, 0, ',', '.')]]"
            class="w-full sm:w-auto"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'fecha'" :direction="$ordenDir" wire:click="ordenarPor('fecha')">Fecha</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Convocatoria</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
                <flux:table.column>Inscritos</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'estado'" :direction="$ordenDir" wire:click="ordenarPor('estado')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($convocatorias as $conv)
                    <flux:table.row wire:key="conv-{{ $conv->id }}">
                        <flux:table.cell>{{ $conv->fecha->format('d-m-Y') }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $conv->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $conv->sede?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $conv->inscripciones_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$conv->estado->value === 'finalizada' ? 'green' : ($conv->estado->value === 'cerrada' ? 'amber' : 'zinc')" size="sm">
                                {{ $conv->estado->etiqueta() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button :href="route('examenes.detalle', $conv)" size="sm" variant="ghost" icon="arrow-right" wire:navigate>
                                    Gestionar
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay convocatorias.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="conv-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">Nueva convocatoria</flux:heading>
            <flux:input wire:model="nombre" label="Nombre" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="fecha" type="date" label="Fecha" required />
                <flux:select wire:model="sede_id" label="Sede" placeholder="Todas las sedes">
                    @foreach ($sedes as $sede)
                        <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
