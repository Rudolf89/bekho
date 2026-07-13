<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Niveles de formación</flux:heading>
            <flux:text class="mt-1">Administra los niveles y su orden</flux:text>
        </div>
        <flux:button wire:click="nuevo" icon="plus" variant="primary">Nuevo nivel</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Orden</flux:table.column>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Contenidos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($niveles as $nivel)
                    <flux:table.row wire:key="nivel-{{ $nivel->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button wire:click="subir({{ $nivel->id }})" icon="chevron-up" variant="ghost" size="xs" inset />
                                <flux:button wire:click="bajar({{ $nivel->id }})" icon="chevron-down" variant="ghost" size="xs" inset />
                                <span class="ml-1 tabular-nums">{{ $nivel->orden }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $nivel->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $nivel->contenidos_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$nivel->activo ? 'green' : 'zinc'" size="sm">
                                {{ $nivel->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button :href="route('formacion.admin.contenidos', $nivel)" icon="list-bullet" variant="ghost" size="sm" wire:navigate>
                                    Contenidos
                                </flux:button>
                                <flux:button wire:click="editar({{ $nivel->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                <flux:button wire:click="alternarActivo({{ $nivel->id }})"
                                    :icon="$nivel->activo ? 'eye-slash' : 'eye'" variant="ghost" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="py-4 text-center">Aún no hay niveles. Crea el primero.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="nivel-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">{{ $editandoId ? 'Editar nivel' : 'Nuevo nivel' }}</flux:heading>

            <flux:input wire:model="nombre" label="Nombre" required />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="3" />
            <flux:input wire:model="orden" type="number" label="Orden" required />
            <flux:switch wire:model="activo" label="Activo" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
