<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Planificaciones de clase</flux:heading>
            <flux:text class="mt-1">Rutinas por grupo etario × nivel × programa</flux:text>
        </div>
        <flux:button wire:click="nueva" icon="plus" variant="primary">Nueva planificación</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar por planificación o programa…" />
        <x-tabla.resumen :total="$planificaciones->count()" etiqueta="planificación" plural="planificaciones" class="w-full sm:w-auto" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Planificación</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'grupo_etario'" :direction="$ordenDir" wire:click="ordenarPor('grupo_etario')">Grupo · Nivel</flux:table.column>
                <flux:table.column>Programa</flux:table.column>
                <flux:table.column>Habilidad</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($planificaciones as $planificacion)
                    <flux:table.row wire:key="plan-{{ $planificacion->id }}">
                        <flux:table.cell variant="strong">{{ $planificacion->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $planificacion->grupo_etario->etiqueta() }} · {{ $planificacion->nivel->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $planificacion->programa?->nombre ?? 'Regular' }}</flux:table.cell>
                        <flux:table.cell>{{ $planificacion->habilidad_vida?->etiqueta() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button :href="route('planificaciones.editar', $planificacion)" size="sm" variant="ghost" icon="pencil-square" wire:navigate>
                                    Editar rutina
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay planificaciones. Crea la primera.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="planificacion-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">Nueva planificación</flux:heading>
            <flux:text>Se crearán automáticamente los 9 bloques y los 4 cuadrantes para que completes su contenido.</flux:text>

            <flux:input wire:model="nombre" label="Nombre" placeholder="Ej: Semana 1 — For Kids Principiantes" required />

            <div class="grid gap-4 sm:grid-cols-2">
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
                <flux:select wire:model="programa_id" label="Programa" placeholder="Regular (sin programa)">
                    @foreach ($programas as $programa)
                        <flux:select.option value="{{ $programa->id }}">{{ $programa->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="habilidad_vida" label="Habilidad para la Vida" placeholder="Sin definir">
                    @foreach ($habilidades as $h)
                        <flux:select.option value="{{ $h->value }}">{{ $h->etiqueta() }}</flux:select.option>
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
