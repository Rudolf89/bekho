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
                <flux:table.column sortable :sorted="$ordenCampo === 'dia_semana'" :direction="$ordenDir" wire:click="ordenarPor('dia_semana')">Día / Hora</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Clase</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'grupo_etario'" :direction="$ordenDir" wire:click="ordenarPor('grupo_etario')">Grupo</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
                <flux:table.column>Instructores</flux:table.column>
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
                        <flux:table.cell>{{ $clase->grupo_etario->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $clase->sede?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @forelse ($clase->instructores as $instructor)
                                <flux:text size="sm" class="block">
                                    {{ $instructor->name }}
                                    <span class="text-zinc-400">· {{ \App\Enums\PapelEnClase::from($instructor->pivot->papel)->etiqueta() }}</span>
                                </flux:text>
                            @empty
                                —
                            @endforelse
                        </flux:table.cell>
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
                <flux:select wire:model="grupo_etario" label="Grupo etario" placeholder="Selecciona">
                    @foreach ($grupos as $g)
                        <flux:select.option value="{{ $g->value }}">{{ $g->etiqueta() }}</flux:select.option>
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
                <flux:select wire:model="planilla_id" label="Planilla (rutina)" placeholder="Sin planilla">
                    @foreach ($planillas as $planilla)
                        <flux:select.option value="{{ $planilla->id }}">{{ $planilla->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Instructores: una clase puede tener varios, cada uno con su papel. --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label>Instructores</flux:label>
                    <flux:button type="button" wire:click="agregarInstructor" icon="plus" variant="ghost" size="sm">Agregar</flux:button>
                </div>

                @forelse ($asignaciones as $indice => $asignacion)
                    <div class="flex items-end gap-2" wire:key="asignacion-{{ $indice }}">
                        <flux:select wire:model="asignaciones.{{ $indice }}.user_id" label="Instructor" placeholder="Selecciona" class="flex-1">
                            @foreach ($instructores as $instructor)
                                <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="asignaciones.{{ $indice }}.papel" label="Papel" class="w-40">
                            @foreach ($papeles as $papel)
                                <flux:select.option value="{{ $papel->value }}">{{ $papel->etiqueta() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button type="button" wire:click="quitarInstructor({{ $indice }})" icon="trash" variant="ghost" size="sm" />
                    </div>
                @empty
                    <flux:text size="sm" class="text-zinc-500">Sin instructores asignados.</flux:text>
                @endforelse

                @error('asignaciones.*.user_id')
                    <flux:text size="sm" class="text-red-500">{{ $message }}</flux:text>
                @enderror
            </div>

            <flux:switch wire:model="activo" label="Activa" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
