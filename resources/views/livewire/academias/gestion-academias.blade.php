<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Academias</flux:heading>
            <flux:text class="mt-1">Escuelas del sistema (nivel raíz)</flux:text>
        </div>
        <flux:button wire:click="nueva" icon="plus" variant="primary">Nueva academia</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar por nombre, correo o teléfono…" />
        <x-tabla.resumen :total="$academias->count()" etiqueta="academia" class="w-full sm:w-auto" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'nombre'" :direction="$ordenDir" wire:click="ordenarPor('nombre')">Nombre</flux:table.column>
                <flux:table.column>Contacto</flux:table.column>
                <flux:table.column>Sedes</flux:table.column>
                <flux:table.column>Usuarios</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'activo'" :direction="$ordenDir" wire:click="ordenarPor('activo')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($academias as $academia)
                    <flux:table.row wire:key="aca-{{ $academia->id }}">
                        <flux:table.cell variant="strong">{{ $academia->nombre }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $academia->email ?? '—' }}
                            @if ($academia->telefono)
                                <flux:text size="sm" class="block">{{ $academia->telefono }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $academia->sedes_count }}</flux:table.cell>
                        <flux:table.cell>{{ $academia->usuarios_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$academia->activo ? 'green' : 'zinc'" size="sm">
                                {{ $academia->activo ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="editar({{ $academia->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                @if ($academia->activo)
                                    <flux:button wire:click="alternarActivo({{ $academia->id }})" icon="eye-slash" variant="ghost" size="sm"
                                        title="Desactivar" wire:confirm="¿Desactivar la academia {{ $academia->nombre }}?" />
                                @else
                                    <flux:button wire:click="alternarActivo({{ $academia->id }})" icon="eye" variant="ghost" size="sm" title="Activar" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay academias.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="academia-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar academia' : 'Nueva academia' }}</flux:heading>

            <flux:input wire:model="nombre" label="Nombre" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="email" type="email" label="Correo electrónico" />
                <flux:input wire:model="telefono" label="Teléfono" />
            </div>
            <flux:input wire:model="logo" label="Logo (URL o ruta)" placeholder="https://…" />

            <flux:switch wire:model="activo" label="Activa" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
