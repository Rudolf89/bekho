<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('examenes.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a exámenes
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">Conteo de graduaciones</flux:heading>
        <flux:text class="mt-1">Total en cascada por instructor (incluye toda su línea descendente)</flux:text>
    </div>

    @unless ($umbralesConfigurados)
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>
                Los umbrales de los collares de máster (Azul, Plateado, Dorado) aún no están configurados,
                así que todavía no se otorgan collares. El conteo en cascada sí se calcula.
            </flux:callout.text>
        </flux:callout>
    @endunless

    <x-tabla.buscador placeholder="Buscar instructor…" />

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Instructor</flux:table.column>
                <flux:table.column>Graduaciones (cascada)</flux:table.column>
                <flux:table.column>Collar</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($instructores as $i)
                    <flux:table.row wire:key="inst-{{ $loop->index }}">
                        <flux:table.cell variant="strong">{{ $i['nombre'] }}</flux:table.cell>
                        <flux:table.cell>{{ $i['conteo'] }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($i['collar'])
                                <flux:badge color="blue" size="sm">{{ ucfirst($i['collar']) }}</flux:badge>
                            @else
                                <flux:text size="sm">—</flux:text>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'No hay instructores para mostrar.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <x-tabla.resumen
            :total="$instructores->count()"
            etiqueta="instructor"
            plural="instructores"
            :sumas="[['etiqueta' => 'Graduaciones (cascada)', 'valor' => number_format($totalGraduaciones, 0, ',', '.')]]"
        />
    </div>
</div>
