<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Estudiantes</flux:heading>
            <flux:text class="mt-1">Fichas de alumnos de la escuela</flux:text>
        </div>
        <div class="flex gap-2">
            @can('create', App\Models\Estudiante::class)
                <flux:button wire:click="nuevo" icon="plus" variant="ghost">Alta rápida</flux:button>
                <flux:button :href="route('inscripcion.crear')" icon="user-plus" variant="primary" wire:navigate>Inscribir alumno</flux:button>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre o RUT" icon="magnifying-glass" />
        <flux:select wire:model.live="filtroGrupo" placeholder="Todos los grupos">
            <flux:select.option value="">Todos los grupos</flux:select.option>
            @foreach ($grupos as $g)
                <flux:select.option value="{{ $g->value }}">{{ $g->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filtroNivel" placeholder="Todos los niveles">
            <flux:select.option value="">Todos los niveles</flux:select.option>
            @foreach ($niveles as $n)
                <flux:select.option value="{{ $n->value }}">{{ $n->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filtroEstado">
            <flux:select.option value="activos">Activos</flux:select.option>
            <flux:select.option value="inactivos">Inactivos</flux:select.option>
            <flux:select.option value="todos">Todos</flux:select.option>
        </flux:select>
    </div>

    <x-tabla.resumen :total="$estudiantes->total()" etiqueta="estudiante" />

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Nombre</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'grupo_etario'" :direction="$ordenDir" wire:click="ordenarPor('grupo_etario')">Grupo</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'nivel'" :direction="$ordenDir" wire:click="ordenarPor('nivel')">Nivel</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'activo'" :direction="$ordenDir" wire:click="ordenarPor('activo')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($estudiantes as $estudiante)
                    <flux:table.row wire:key="est-{{ $estudiante->id }}">
                        <flux:table.cell variant="strong">
                            {{ $estudiante->nombre }}
                            @if ($estudiante->rut)
                                <flux:text size="sm" class="block">{{ $estudiante->rut }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $estudiante->grupo_etario->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $estudiante->nivel->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $estudiante->sede?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$estudiante->activo ? 'green' : 'zinc'" size="sm">
                                {{ $estudiante->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $estudiante)
                                    <flux:button wire:click="editar({{ $estudiante->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                    <flux:button wire:click="alternarActivo({{ $estudiante->id }})"
                                        :icon="$estudiante->activo ? 'user-minus' : 'user'" variant="ghost" size="sm"
                                        :title="$estudiante->activo ? 'Desactivar' : 'Activar'"
                                        @if ($estudiante->activo) wire:confirm="¿Desactivar a {{ $estudiante->nombre }}?" @endif />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">No se encontraron estudiantes.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>{{ $estudiantes->links() }}</div>

    {{-- Formulario --}}
    <flux:modal name="estudiante-modal" wire:model="mostrarModal" class="max-w-2xl md:min-w-2xl">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar estudiante' : 'Nuevo estudiante' }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="nombre" label="Nombre" required />
                <flux:input wire:model="rut" label="RUT" />
                <flux:input wire:model.live="fecha_nacimiento" type="date" label="Fecha de nacimiento"
                    :description="$this->edad() !== null ? 'Edad: '.$this->edad().' '.($this->edad() === 1 ? 'año' : 'años') : null" />
                <flux:select wire:model.live="grupo_etario" label="Grupo etario" placeholder="Selecciona">
                    @foreach ($grupos as $g)
                        <flux:select.option value="{{ $g->value }}">{{ $g->etiqueta() }} ({{ $g->rangoEdad() }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="grado_id" label="Grado (cinturón)" placeholder="Sin grado (nuevo)">
                    @foreach ($this->gradosDisponibles() as $grado)
                        <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div>
                    <flux:label>Nivel</flux:label>
                    <div class="mt-2 flex h-10 items-center gap-2">
                        <flux:badge :color="$this->nivelDerivado()->color()">
                            {{ $this->nivelDerivado()->etiqueta() }}
                        </flux:badge>
                        <flux:text size="sm" class="text-zinc-500">Según el cinturón</flux:text>
                    </div>
                </div>
                <flux:select wire:model="sede_id" label="Sede" placeholder="Sin sede">
                    @foreach ($sedes as $sede)
                        <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="telefono_contacto" label="Teléfono de contacto" />
                <flux:input wire:model="email_contacto" type="email" label="Correo de contacto" />
            </div>

            {{-- Aviso cuando el alumno está por cumplir la edad del grupo siguiente. --}}
            @php($sugerencia = $this->sugerenciaProximoGrupo())
            @if ($sugerencia)
                <flux:callout size="sm" icon="arrow-trending-up" color="amber">
                    <flux:callout.text>
                        Está por cumplir {{ $sugerencia['edadProxima'] }} años{{ $sugerencia['meses'] > 0 ? ' (en ~'.$sugerencia['meses'].' '.($sugerencia['meses'] === 1 ? 'mes' : 'meses').')' : ' este mes' }}.
                        Si corresponde, puedes moverlo a <strong>{{ $sugerencia['grupo']->etiqueta() }}</strong> ({{ $sugerencia['grupo']->rangoEdad() }}).
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="ghost" icon="arrow-up-right"
                            wire:click="cambiarGrupo('{{ $sugerencia['grupo']->value }}')">
                            Pasar a {{ $sugerencia['grupo']->etiqueta() }}
                        </flux:button>
                    </x-slot>
                </flux:callout>
            @endif

            <div>
                <flux:label>Programas</flux:label>
                <div class="mt-2 flex flex-wrap gap-4">
                    @foreach ($listaProgramas as $programa)
                        <flux:checkbox wire:model="programas" value="{{ $programa->id }}" label="{{ $programa->nombre }}" />
                    @endforeach
                </div>
            </div>

            @if ($listaApoderados->isNotEmpty())
                <div>
                    <flux:label>Apoderados</flux:label>
                    <div class="mt-2 max-h-40 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($listaApoderados as $apoderado)
                            <flux:checkbox wire:model="apoderados" value="{{ $apoderado->id }}" label="{{ $apoderado->name }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            <flux:switch wire:model="activo" label="Activo" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
