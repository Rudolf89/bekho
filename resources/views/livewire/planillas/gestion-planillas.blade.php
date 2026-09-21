<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Gestión · Programa</flux:text>
        <flux:heading size="xl" class="mt-1">Planillas</flux:heading>
    </div>

    {{-- El listado salió del prototipo, no de un documento de la federación. --}}
    @if ($planillas->contains(fn ($p) => ! $p->verificado))
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.text>
                Estas planillas vienen del prototipo de pantallas y están <strong>sin verificar</strong>:
                revisa sus columnas antes de repartirlas. Las que aparecen sin columnas no se pueden
                imprimir hasta que se definan.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading size="lg" class="flex-1">Planillas del programa</flux:heading>

            @if ($puedeEditar)
                <flux:button size="sm" variant="primary" icon="plus" wire:click="nueva">Nueva planilla</flux:button>
            @endif
        </div>

        <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @forelse ($planillas as $planilla)
                <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                    <flux:icon.document-text class="mt-0.5 size-5 shrink-0 text-zinc-400" />

                    <div class="min-w-0 flex-1 basis-64">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading>{{ $planilla->nombre }}</flux:heading>
                            <flux:badge size="sm" :color="$planilla->estado->color()">
                                {{ $planilla->estado->etiqueta() }} · {{ $planilla->etiquetaVersion() }}
                            </flux:badge>
                        </div>

                        <flux:text size="sm" class="mt-0.5">
                            @if ($planilla->columnas->isNotEmpty())
                                {{ $planilla->columnas->pluck('titulo')->join(', ') }}
                            @else
                                <span class="text-amber-600 dark:text-amber-500">Sin columnas definidas</span>
                            @endif
                        </flux:text>
                    </div>

                    <flux:text size="sm" class="shrink-0 basis-32">{{ $planilla->uso }}</flux:text>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($planilla->columnas->isNotEmpty())
                            <flux:button size="sm" variant="filled" icon="printer"
                                         :href="route('planillas.imprimir', $planilla)" target="_blank">
                                Imprimir
                            </flux:button>
                        @else
                            <flux:button size="sm" variant="filled" icon="printer" disabled>Imprimir</flux:button>
                        @endif

                        @if ($puedeEditar)
                            <flux:button size="sm" variant="ghost" wire:click="editar({{ $planilla->id }})">Editar</flux:button>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-5 py-6">
                    <flux:text>Todavía no hay planillas en el catálogo.</flux:text>
                </li>
            @endforelse
        </ul>
    </div>

    {{-- Editor: solo se pinta para quien puede tocar el catálogo. --}}
    @if ($puedeEditar)
    <flux:modal wire:model.self="mostrarModal" class="w-full md:max-w-2xl">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $this->editandoId ? 'Editar planilla' : 'Nueva planilla' }}</flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="nombre" label="Nombre" />
                <flux:input wire:model="uso" label="Uso" placeholder="Examen del ciclo, Torneo…" />
            </div>

            <flux:input wire:model="descripcion" label="Descripción" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:select wire:model="estado" label="Estado">
                    @foreach ($estados as $opcion)
                        <flux:select.option value="{{ $opcion->value }}">{{ $opcion->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" wire:model="version" label="Versión" min="1" max="99" />
                <flux:input type="number" wire:model="filas" label="Filas en blanco" min="1" max="60" />
            </div>

            <div>
                <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Columnas</flux:text>
                <flux:text size="sm" class="mb-2">Son los encabezados de la grilla que se imprime.</flux:text>

                <div class="space-y-2">
                    @foreach ($columnas as $i => $columna)
                        <div class="flex items-center gap-2" wire:key="col-{{ $i }}">
                            <flux:input wire:model="columnas.{{ $i }}" placeholder="Título de la columna" class="flex-1" />
                            <flux:button size="sm" variant="subtle" icon="trash" wire:click="quitarColumna({{ $i }})" />
                        </div>
                    @endforeach
                </div>

                <flux:button class="mt-2" size="sm" variant="ghost" icon="plus" wire:click="agregarColumna">
                    Agregar columna
                </flux:button>
            </div>

            <div class="flex justify-end gap-2">
                @if ($this->editandoId)
                    <flux:button variant="subtle" wire:click="eliminar({{ $this->editandoId }})"
                                 wire:confirm="¿Eliminar esta planilla del catálogo?">
                        Eliminar
                    </flux:button>
                @endif
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
</div>
