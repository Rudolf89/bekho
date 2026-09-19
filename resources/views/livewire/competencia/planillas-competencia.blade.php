<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Planillas de competencia</flux:heading>
            <flux:text class="mt-1">Pruebas de certificación de planillero (competidores y jueces de práctica)</flux:text>
        </div>
        @unless (auth()->user()?->esSoloLectura())
            <flux:button wire:click="nueva" icon="plus" variant="primary">Nueva planilla</flux:button>
        @endunless
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Prueba</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column>Grupo de edad</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Competidores</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($planillas as $planilla)
                    <flux:table.row wire:key="planilla-{{ $planilla->id }}">
                        <flux:table.cell variant="strong">{{ $planilla->prueba?->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $planilla->categoria?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $planilla->grupoEdad?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $planilla->fecha?->format('d-m-Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $planilla->competidores_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$planilla->estado->color()" size="sm">{{ $planilla->estado->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button :href="route('competencia.ver', $planilla)" wire:navigate size="sm" variant="ghost" icon="arrow-right">Abrir</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <flux:text class="py-6 text-center">Aún no hay planillas de competencia.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>{{ $planillas->links() }}</div>

    {{-- Modal nueva planilla --}}
    <flux:modal name="planilla-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="crear" class="space-y-5">
            <flux:heading size="lg">Nueva planilla</flux:heading>

            <flux:select wire:model="prueba_id" label="Prueba" placeholder="Selecciona">
                @foreach ($pruebas as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="categoria_competencia_id" label="Categoría" placeholder="—">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($categorias as $c)
                        <flux:select.option value="{{ $c->id }}">{{ $c->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="grupo_edad_id" label="Grupo de edad" placeholder="—">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($gruposEdad as $g)
                        <flux:select.option value="{{ $g->id }}">{{ $g->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="fecha" type="date" label="Fecha" />
                <flux:input wire:model="genero" label="Género" placeholder="Masculino / Femenino / Mixto" />
                <flux:input wire:model="nro_pista" label="N° de pista" />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
