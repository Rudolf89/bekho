<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button :href="route('formacion.admin.niveles')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a niveles
        </flux:button>
    </div>

    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Contenidos</flux:heading>
            <flux:text class="mt-1">Nivel: {{ $nivel->nombre }}</flux:text>
        </div>
        <flux:button wire:click="nuevo" icon="plus" variant="primary">Nuevo contenido</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Orden</flux:table.column>
                <flux:table.column>Título</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($contenidos as $contenido)
                    <flux:table.row wire:key="contenido-{{ $contenido->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button wire:click="subir({{ $contenido->id }})" icon="chevron-up" variant="ghost" size="xs" inset />
                                <flux:button wire:click="bajar({{ $contenido->id }})" icon="chevron-down" variant="ghost" size="xs" inset />
                                <span class="ml-1 tabular-nums">{{ $contenido->orden }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $contenido->titulo }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="blue" size="sm">{{ $contenido->tipo->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$contenido->activo ? 'green' : 'zinc'" size="sm">
                                {{ $contenido->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="editar({{ $contenido->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                <flux:button wire:click="alternarActivo({{ $contenido->id }})"
                                    :icon="$contenido->activo ? 'eye-slash' : 'eye'" variant="ghost" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="py-4 text-center">Este nivel aún no tiene contenidos.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="contenido-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">{{ $editandoId ? 'Editar contenido' : 'Nuevo contenido' }}</flux:heading>

            <flux:input wire:model="titulo" label="Título" required />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="2" />

            <flux:select wire:model.live="tipo" label="Tipo">
                @foreach ($this->tiposDisponibles() as $valor => $etiqueta)
                    <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Campos según el tipo elegido --}}
            @if ($tipo === 'texto')
                <flux:textarea wire:model="cuerpo" label="Cuerpo del contenido" rows="6"
                    description="Texto redactado por el grupo (autoría propia)." />
            @else
                <flux:input wire:model="url_recurso" type="url" label="URL del recurso"
                    placeholder="https://..."
                    description="El video o documento se aloja de forma externa; aquí solo se guarda su enlace." />
            @endif

            <flux:input wire:model="orden" type="number" label="Orden" required />
            <flux:switch wire:model="activo" label="Activo" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
