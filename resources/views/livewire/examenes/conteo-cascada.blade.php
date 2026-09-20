<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('examenes.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a exámenes
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">Créditos de graduación</flux:heading>
        <flux:text class="mt-1">Cada graduación suma un crédito al instructor de origen y a toda su cadena de supervisión hacia arriba.</flux:text>
    </div>

    @unless ($umbralesConfigurados)
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>
                Los umbrales de los distintivos del profesor (Negro, Negro-Azul-Negro, Negro-Plateado-Negro,
                Negro-Dorado-Negro) aún no están configurados, así que todavía no se otorgan distintivos.
                Los créditos sí se acumulan.
            </flux:callout.text>
        </flux:callout>
    @endunless

    {{-- Mi perfil de instructor --}}
    @if ($perfil)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-3">Mi perfil de instructor</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <flux:text size="sm" class="text-zinc-500">Rango</flux:text>
                    <flux:text class="font-semibold">{{ $perfil['rango']?->nombre ?? '—' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">Distintivo actual</flux:text>
                    <flux:text class="font-semibold">{{ $perfil['distintivo']?->nombre ?? 'Sin distintivo aún' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">Créditos acumulados</flux:text>
                    <flux:text class="font-semibold tabular-nums">{{ $perfil['total'] }}</flux:text>
                </div>
            </div>
            @if ($perfil['cadena']->isNotEmpty())
                <div class="mt-4">
                    <flux:text size="sm" class="text-zinc-500">Mi cadena de supervisión</flux:text>
                    <div class="mt-1 flex flex-wrap items-center gap-1">
                        @foreach ($perfil['cadena'] as $sup)
                            @if (! $loop->first)<flux:icon.arrow-right class="size-3 text-zinc-400" />@endif
                            <flux:badge size="sm" color="zinc" wire:key="cad-{{ $sup->id }}">{{ $sup->nombreCompleto() }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar instructor…" />
        <x-tabla.resumen
            :total="$instructores->count()"
            etiqueta="instructor"
            plural="instructores"
            :sumas="[['etiqueta' => 'Créditos acumulados', 'valor' => number_format($totalGraduaciones, 0, ',', '.')]]"
            class="w-full sm:w-auto"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Instructor</flux:table.column>
                <flux:table.column>Créditos</flux:table.column>
                <flux:table.column>Distintivo</flux:table.column>
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
    </div>
</div>
