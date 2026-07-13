<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Clases y horario</flux:heading>
            <flux:text class="mt-1">Clases recurrentes de la escuela</flux:text>
        </div>
        <flux:button wire:click="nuevo" icon="plus" variant="primary">Nueva clase</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Día / Hora</flux:table.column>
                <flux:table.column>Clase</flux:table.column>
                <flux:table.column>Grupo · Nivel</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
                <flux:table.column>Instructor</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($clases as $clase)
                    <flux:table.row wire:key="clase-{{ $clase->id }}">
                        <flux:table.cell variant="strong">
                            {{ $clase->dia_semana->etiqueta() }}
                            <flux:text size="sm" class="block">
                                {{ substr((string) $clase->hora_inicio, 0, 5) }}{{ $clase->hora_fin ? ' – '.substr((string) $clase->hora_fin, 0, 5) : '' }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $clase->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $clase->grupo_etario->etiqueta() }} · {{ $clase->nivel->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $clase->sede?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $clase->instructor?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="editar({{ $clase->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                <flux:button wire:click="alternarActivo({{ $clase->id }})"
                                    :icon="$clase->activo ? 'eye-slash' : 'eye'" variant="ghost" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">Aún no hay clases. Crea la primera.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="clase-modal" wire:model="mostrarModal" class="max-w-xl md:min-w-xl">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar clase' : 'Nueva clase' }}</flux:heading>

            <flux:input wire:model="nombre" label="Nombre de la clase" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="sede_id" label="Sede" placeholder="Selecciona">
                    @foreach ($sedes as $sede)
                        <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="instructor_id" label="Instructor a cargo" placeholder="Sin asignar">
                    @foreach ($instructores as $instructor)
                        <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="grupo_etario" label="Grupo etario" placeholder="Selecciona">
                    @foreach ($grupos as $g)
                        <flux:select.option value="{{ $g->value }}">{{ $g->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="nivel" label="Nivel" placeholder="Selecciona">
                    @foreach ($niveles as $n)
                        <flux:select.option value="{{ $n->value }}">{{ $n->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="dia_semana" label="Día" placeholder="Selecciona">
                    @foreach ($dias as $d)
                        <flux:select.option value="{{ $d->value }}">{{ $d->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="grid grid-cols-2 gap-2">
                    <flux:input wire:model="hora_inicio" type="time" label="Inicio" />
                    <flux:input wire:model="hora_fin" type="time" label="Fin" />
                </div>
            </div>

            <flux:switch wire:model="activo" label="Activa" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
