<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('programas.admin.etapas')" wire:navigate>Etapas</flux:button>
        <div class="mt-2 flex items-center justify-between">
            <flux:heading size="xl">Contenidos · {{ $etapa->nombre }}</flux:heading>
            <flux:button variant="primary" icon="plus" wire:click="nuevo">Nuevo contenido</flux:button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Orden</flux:table.column>
                <flux:table.column>Título</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($contenidos as $contenido)
                    <flux:table.row wire:key="cont-{{ $contenido->id }}">
                        <flux:table.cell>{{ $contenido->orden }}</flux:table.cell>
                        <flux:table.cell>{{ $contenido->titulo }}</flux:table.cell>
                        <flux:table.cell>{{ $contenido->tipo->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$contenido->activo ? 'green' : 'zinc'">{{ $contenido->activo ? 'Activo' : 'Inactivo' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button variant="ghost" size="xs" icon="pencil" wire:click="editar({{ $contenido->id }})" />
                            <flux:button variant="ghost" size="xs" icon="power" wire:click="alternarActivo({{ $contenido->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="mostrarModal" class="w-full max-w-lg">
        <form wire:submit="guardar" class="space-y-4">
            <flux:heading size="lg">{{ $editandoId ? 'Editar contenido' : 'Nuevo contenido' }}</flux:heading>
            <flux:input wire:model="titulo" label="Título" />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="2" />
            <flux:select wire:model.live="tipo" label="Tipo">
                @foreach ($this->tiposDisponibles() as $valor => $etiqueta)
                    <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
                @endforeach
            </flux:select>
            @if ($tipo === 'texto')
                <flux:textarea wire:model="cuerpo" label="Cuerpo" rows="6" />
            @else
                <flux:input wire:model="url_recurso" label="URL del recurso" />
            @endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:input type="number" wire:model="orden" label="Orden" />
                <flux:checkbox wire:model="activo" label="Activo" class="mt-6" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
