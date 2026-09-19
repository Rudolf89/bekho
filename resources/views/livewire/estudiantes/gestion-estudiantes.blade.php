<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Alumnos</flux:heading>
            <flux:text class="mt-1">Matrículas de la escuela</flux:text>
        </div>
        <div class="flex gap-2">
            @can('create', App\Models\Matricula::class)
                <flux:button wire:click="nuevo" icon="plus" variant="ghost">Alta rápida</flux:button>
                <flux:button :href="route('inscripcion.crear')" icon="user-plus" variant="primary" wire:navigate>Inscribir alumno</flux:button>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre o documento" icon="magnifying-glass" />
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

    <x-tabla.resumen :total="$matriculas->total()" etiqueta="alumno" :plural="'alumnos'" />

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'grupo_etario'" :direction="$ordenDir" wire:click="ordenarPor('grupo_etario')">Grupo</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'nivel'" :direction="$ordenDir" wire:click="ordenarPor('nivel')">Nivel</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'estado'" :direction="$ordenDir" wire:click="ordenarPor('estado')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($matriculas as $matricula)
                    <flux:table.row wire:key="mat-{{ $matricula->id }}">
                        <flux:table.cell variant="strong">{{ $matricula->persona?->nombreCompleto() }}</flux:table.cell>
                        <flux:table.cell>{{ $matricula->grupo_etario->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>{{ $matricula->nivel?->etiqueta() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $matricula->sede?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$matricula->estado->color()" size="sm">{{ $matricula->estado->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $matricula)
                                    <flux:button wire:click="editar({{ $matricula->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                    @if ($matricula->estado->value === 'activa')
                                        <flux:button wire:click="alternarActivo({{ $matricula->id }})" icon="user-minus" variant="ghost" size="sm"
                                            title="Retirar" wire:confirm="¿Retirar a {{ $matricula->persona?->nombreCompleto() }}?" />
                                    @else
                                        <flux:button wire:click="alternarActivo({{ $matricula->id }})" icon="user" variant="ghost" size="sm" title="Reactivar" />
                                    @endif
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">No se encontraron alumnos.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>{{ $matriculas->links() }}</div>

    {{-- Formulario de alta rápida / edición --}}
    <flux:modal name="estudiante-modal" wire:model="mostrarModal" class="max-w-2xl md:min-w-2xl">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar alumno' : 'Alta rápida de alumno' }}</flux:heading>

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
                        <flux:badge :color="$this->nivelDerivado()->color()">{{ $this->nivelDerivado()->etiqueta() }}</flux:badge>
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

            <flux:switch wire:model="activo" label="Matrícula activa" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
