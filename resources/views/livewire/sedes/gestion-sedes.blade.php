<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Sedes</flux:heading>
            <flux:text class="mt-1">Sucursales de la escuela</flux:text>
        </div>
        <flux:button wire:click="nueva" icon="plus" variant="primary">Nueva sede</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar por nombre, comuna, dirección o academia…" />
        <x-tabla.resumen :total="$sedes->count()" etiqueta="sede" class="w-full sm:w-auto" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Nombre</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'comuna'" :direction="$ordenDir" wire:click="ordenarPor('comuna')">Comuna</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                @if ($this->esSuperAdmin())
                    <flux:table.column>Academia</flux:table.column>
                @endif
                <flux:table.column sortable :sorted="$ordenCampo === 'activo'" :direction="$ordenDir" wire:click="ordenarPor('activo')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($sedes as $sede)
                    <flux:table.row wire:key="sede-{{ $sede->id }}">
                        <flux:table.cell variant="strong">
                            {{ $sede->nombre }}
                            @if ($sede->direccion)
                                <flux:text size="sm" class="block">{{ $sede->direccion }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $sede->comuna ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $sede->tipo->etiqueta() }}
                            @if ($sede->privada)
                                <flux:badge color="amber" size="sm">Privada</flux:badge>
                            @endif
                        </flux:table.cell>
                        @if ($this->esSuperAdmin())
                            <flux:table.cell>{{ $sede->academia?->nombre ?? '—' }}</flux:table.cell>
                        @endif
                        <flux:table.cell>
                            <flux:badge :color="$sede->activo ? 'green' : 'zinc'" size="sm">
                                {{ $sede->activo ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="editar({{ $sede->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                <flux:button wire:click="alternarActivo({{ $sede->id }})"
                                    :icon="$sede->activo ? 'eye-slash' : 'eye'" variant="ghost" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay sedes. Crea la primera.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="sede-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar sede' : 'Nueva sede' }}</flux:heading>

            <flux:input wire:model="nombre" label="Nombre" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="direccion" label="Dirección" />
                <flux:select wire:model="comuna" label="Comuna" placeholder="Selecciona una comuna">
                    {{-- Conserva un valor previo que no esté en la lista (p. ej. otra región). --}}
                    @if ($comuna && ! in_array($comuna, $comunas, true))
                        <flux:select.option value="{{ $comuna }}">{{ $comuna }}</flux:select.option>
                    @endif
                    @foreach ($comunas as $c)
                        <flux:select.option value="{{ $c }}">{{ $c }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="tipo" label="Tipo de sede">
                    @foreach ($tipos as $t)
                        <flux:select.option value="{{ $t->value }}">{{ $t->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="privada" label="Privada (solo miembros de la entidad)" />
                </div>
            </div>

            @if ($this->esSuperAdmin())
                <flux:select wire:model="academia_id" label="Academia" placeholder="Selecciona una academia">
                    @foreach ($academias as $academia)
                        <flux:select.option value="{{ $academia->id }}">{{ $academia->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            @if ($listaInstructores->isNotEmpty())
                <div>
                    <flux:label>Instructores</flux:label>
                    <div class="mt-2 max-h-40 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($listaInstructores as $instructor)
                            <flux:checkbox wire:model="instructores" value="{{ $instructor->id }}" label="{{ $instructor->name }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            <flux:switch wire:model="activo" label="Activa" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
