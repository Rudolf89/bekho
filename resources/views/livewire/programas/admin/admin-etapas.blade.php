<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Etapas de programas</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="nuevo">Nueva etapa</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Programa</flux:table.column>
                <flux:table.column>Etapa</flux:table.column>
                <flux:table.column>Horas</flux:table.column>
                <flux:table.column>Contenidos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($etapas as $etapa)
                    <flux:table.row wire:key="etapa-{{ $etapa->id }}">
                        <flux:table.cell>{{ $etapa->programa?->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $etapa->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $etapa->horas_requeridas ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('programas.admin.contenidos', $etapa)" wire:navigate>{{ $etapa->contenidos_count }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$etapa->activo ? 'green' : 'zinc'">{{ $etapa->activo ? 'Activa' : 'Inactiva' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button variant="ghost" size="xs" icon="pencil" wire:click="editar({{ $etapa->id }})" />
                            <flux:button variant="ghost" size="xs" icon="power" wire:click="alternarActivo({{ $etapa->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="mostrarModal" class="w-full max-w-lg">
        <form wire:submit="guardar" class="space-y-4">
            <flux:heading size="lg">{{ $editandoId ? 'Editar etapa' : 'Nueva etapa' }}</flux:heading>
            <flux:select wire:model="programa_id" label="Programa" placeholder="Elige…">
                @foreach ($programas as $programa)
                    <flux:select.option value="{{ $programa->id }}">{{ $programa->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="nombre" label="Nombre" />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="2" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <flux:input type="number" wire:model="orden" label="Orden" />
                <flux:input type="number" wire:model="horas_requeridas" label="Horas" />
                <flux:input type="number" wire:model="edad_minima" label="Edad mín." />
            </div>
            <flux:checkbox wire:model="activo" label="Activa" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
