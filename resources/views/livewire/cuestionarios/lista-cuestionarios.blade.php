<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Cuestionarios</flux:heading>
            <flux:text class="mt-1">Evaluaciones autocorregidas. Rinde las veces que quieras.</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('cuestionarios.mis-intentos')" icon="clock" variant="subtle" size="sm" wire:navigate>
                Mis intentos
            </flux:button>
            @if ($puedeGestionar)
                <flux:button :href="route('cuestionarios.resultados')" icon="chart-bar" variant="subtle" size="sm" wire:navigate>
                    Resultados
                </flux:button>
                <flux:button :href="route('cuestionarios.crear')" icon="plus" variant="primary" size="sm" wire:navigate>
                    Nuevo cuestionario
                </flux:button>
            @endif
        </div>
    </div>

    <x-tabla.buscador placeholder="Buscar por título o área…" />

    @if ($cuestionarios->isEmpty())
        <flux:callout icon="clipboard-document-list">
            @if ($buscar !== '')
                Sin resultados para tu búsqueda.
            @else
                Aún no hay cuestionarios. @if ($puedeGestionar) Crea el primero con “Nuevo cuestionario”. @endif
            @endif
        </flux:callout>
    @endif

    <div class="space-y-3">
        @foreach ($cuestionarios as $c)
            <div wire:key="cu-{{ $c->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg">{{ $c->titulo }}</flux:heading>
                            @if ($c->area)
                                <flux:badge size="sm" color="sky">{{ $c->area }}</flux:badge>
                            @endif
                            @unless ($c->activo)
                                <flux:badge size="sm" color="zinc">Inactivo</flux:badge>
                            @endunless
                            @if (in_array($c->id, $aprobados))
                                <flux:badge size="sm" color="green">Aprobado</flux:badge>
                            @endif
                        </div>
                        @if ($c->descripcion)
                            <flux:text size="sm" class="mt-1">{{ $c->descripcion }}</flux:text>
                        @endif
                        <flux:text size="sm" class="mt-2 block text-zinc-500">
                            {{ $c->preguntas_count }} preguntas · aprueba con {{ $c->umbral_aprobacion }}%
                            @if (isset($mejores[$c->id]))
                                · tu mejor: <span class="font-semibold {{ $mejores[$c->id] >= $c->umbral_aprobacion ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $mejores[$c->id] }}%</span>
                            @endif
                        </flux:text>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($c->preguntas_count > 0)
                            <flux:button :href="route('cuestionarios.rendir', $c)" icon="play" size="sm" variant="primary" wire:navigate>
                                Rendir
                            </flux:button>
                        @endif
                        @if ($puedeGestionar)
                            <flux:button :href="route('cuestionarios.editar', $c)" icon="pencil-square" size="sm" variant="subtle" wire:navigate>
                                Editar
                            </flux:button>
                            <flux:button
                                icon="trash"
                                size="sm"
                                variant="subtle"
                                wire:click="eliminar({{ $c->id }})"
                                wire:confirm="¿Eliminar este cuestionario y todos sus intentos?"
                            />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($cuestionarios->isNotEmpty())
        <x-tabla.resumen :total="$cuestionarios->count()" etiqueta="cuestionario" class="rounded-xl border border-zinc-200 dark:border-zinc-700" />
    @endif
</div>
