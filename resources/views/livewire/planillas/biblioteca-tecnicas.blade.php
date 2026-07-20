<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Biblioteca de técnicas</flux:heading>
        <flux:text class="mt-1">Currículo ATA: patadas, formas, manos, tricks y armas ({{ $total }})</flux:text>
    </div>

    {{-- Filtros --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="buscar" placeholder="Buscar técnica o cinturón" icon="magnifying-glass" />
        <flux:select wire:model.live="categoria" placeholder="Todas las categorías">
            <flux:select.option value="">Todas las categorías</flux:select.option>
            @foreach ($categorias as $c)
                <flux:select.option value="{{ $c->value }}">{{ $c->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="modalidad" placeholder="Todas las modalidades">
            <flux:select.option value="">Todas las modalidades</flux:select.option>
            @foreach ($modalidades as $m)
                <flux:select.option value="{{ $m->value }}">{{ $m->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @forelse ($grupos as $categoriaValor => $tecnicas)
        @php($cat = \App\Enums\CategoriaTecnica::from($categoriaValor))
        <div class="space-y-3">
            <flux:heading size="lg">{{ $cat->etiqueta() }} <span class="text-zinc-400">({{ $tecnicas->count() }})</span></flux:heading>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($tecnicas as $tecnica)
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800" wire:key="tec-{{ $tecnica->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <flux:heading class="min-w-0">{{ $tecnica->nombre }}</flux:heading>
                            <div class="flex shrink-0 flex-wrap justify-end gap-1">
                                @if ($tecnica->modalidad)
                                    <flux:badge size="sm" :color="$tecnica->modalidad->value === 'tradicional' ? 'zinc' : 'purple'">{{ $tecnica->modalidad->etiqueta() }}</flux:badge>
                                @endif
                                <flux:badge size="sm" :color="$tecnica->core ? 'green' : 'amber'">{{ $tecnica->core ? 'Core' : 'Electivo' }}</flux:badge>
                            </div>
                        </div>

                        <div class="mt-1 flex flex-wrap gap-1">
                            @if ($tecnica->subcategoria)
                                <flux:badge size="sm" color="zinc">{{ $tecnica->subcategoria }}</flux:badge>
                            @endif
                            @if ($tecnica->cinturon)
                                <flux:badge size="sm" color="blue">{{ $tecnica->cinturon }}</flux:badge>
                            @endif
                            @if ($tecnica->nivel)
                                <flux:badge size="sm" :color="$tecnica->nivel->color()">{{ $tecnica->nivel->etiqueta() }}</flux:badge>
                            @endif
                        </div>

                        @if ($tecnica->significado)
                            <flux:text size="sm" class="mt-2 italic">«{{ $tecnica->significado }}»</flux:text>
                        @endif
                        @if ($tecnica->descripcion)
                            <flux:text size="sm" class="mt-2 block text-zinc-600 dark:text-zinc-300">{{ $tecnica->descripcion }}</flux:text>
                        @endif

                        @if ($tecnica->pasos->isNotEmpty())
                            <div x-data="{ open: false }" class="mt-3">
                                <button type="button" x-on:click="open = !open" class="text-sm font-semibold text-red-600 dark:text-red-400">
                                    <span x-text="open ? '▾ Ocultar secuencia' : '▸ Ver secuencia ({{ $tecnica->pasos->count() }} pasos)'"></span>
                                </button>
                                <div x-show="open" x-cloak class="mt-2 space-y-2">
                                    @foreach ($tecnica->pasos->groupBy('segmento') as $segmento => $pasos)
                                        <div>
                                            @if ($segmento)
                                                <flux:text size="sm" class="font-semibold">{{ $segmento }}</flux:text>
                                            @endif
                                            <ol class="ml-1 mt-1 space-y-0.5 text-sm text-zinc-600 dark:text-zinc-300">
                                                @foreach ($pasos as $paso)
                                                    <li class="flex gap-2"><span class="text-zinc-400">{{ $loop->iteration }}.</span><span>{{ $paso->texto }}</span></li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>No se encontraron técnicas con esos filtros.</flux:text>
        </div>
    @endforelse
</div>
