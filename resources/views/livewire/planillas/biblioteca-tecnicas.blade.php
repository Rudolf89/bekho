<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Biblioteca de técnicas</flux:heading>
        <flux:text class="mt-1">Currículo ATA con su descripción y secuencia paso a paso: patadas, formas, manos, tricks y armas ({{ $total }})</flux:text>
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
            <div class="border-b border-zinc-200 pb-2 dark:border-zinc-700">
                <flux:heading size="lg">{{ $cat->emoji() }} {{ $cat->etiqueta() }} <span class="text-zinc-400">({{ $tecnicas->count() }})</span></flux:heading>
                <flux:text size="sm" class="mt-0.5">{{ $cat->descripcion() }}</flux:text>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($tecnicas as $tecnica)
                    @php($cinturones = $tecnica->grados->pluck('color')->unique()->values())
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800" wire:key="tec-{{ $tecnica->id }}">
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
                            @foreach ($cinturones as $color)
                                <flux:badge size="sm" color="blue">Cinturón {{ $color }}</flux:badge>
                            @endforeach
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

                        {{-- Paso a paso (colapsado por defecto para no saturar) --}}
                        @if ($tecnica->pasos->isNotEmpty())
                            @php($enTabla = $tecnica->pasos->contains(fn ($p) => $p->lado || $p->postura || $p->seccion))
                            <div x-data="{ open: false }" class="mt-auto pt-3">
                                <button type="button" x-on:click="open = !open"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-left text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-100 dark:bg-zinc-900/50 dark:text-zinc-200 dark:hover:bg-zinc-900">
                                    <span>Paso a paso · {{ $tecnica->pasos->count() }} movimientos</span>
                                    <span class="text-red-600 dark:text-red-400" x-text="open ? '▾' : '▸'"></span>
                                </button>

                                <div x-show="open" x-cloak class="mt-2">
                                    @if ($enTabla)
                                        {{-- Formas: tabla escaneable --}}
                                        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            <table class="w-full min-w-[420px] text-left text-xs">
                                                <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-900/60 dark:text-zinc-400">
                                                    <tr>
                                                        <th class="w-8 px-2 py-1.5 text-center font-semibold">#</th>
                                                        <th class="w-12 px-2 py-1.5 font-semibold">Lado</th>
                                                        <th class="px-2 py-1.5 font-semibold">Técnica</th>
                                                        <th class="px-2 py-1.5 font-semibold">Postura</th>
                                                        <th class="px-2 py-1.5 font-semibold">Sección</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                                    @foreach ($tecnica->pasos as $paso)
                                                        <tr class="odd:bg-white even:bg-zinc-50/60 dark:odd:bg-zinc-800 dark:even:bg-zinc-900/40">
                                                            <td class="px-2 py-1.5 text-center font-semibold text-zinc-400">{{ $paso->orden }}</td>
                                                            <td class="px-2 py-1.5 text-zinc-500">{{ $paso->lado ?: '—' }}</td>
                                                            <td class="px-2 py-1.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $paso->texto }}</td>
                                                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-300">{{ $paso->postura ?: '—' }}</td>
                                                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-300">{{ $paso->seccion ?: '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        {{-- Armas y otras secuencias: lista por segmento --}}
                                        <div class="space-y-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                                            @foreach ($tecnica->pasos->groupBy('segmento') as $segmento => $pasos)
                                                <div>
                                                    @if ($segmento)
                                                        <flux:text size="sm" class="font-semibold text-red-700 dark:text-red-400">{{ $segmento }}</flux:text>
                                                    @endif
                                                    <ol class="ml-1 mt-1 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                        @foreach ($pasos as $paso)
                                                            <li class="flex gap-2">
                                                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">{{ $loop->iteration }}</span>
                                                                <span class="pt-0.5">{{ $paso->texto }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ol>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif ($cat->llevaSecuencia())
                            <div class="mt-auto pt-3">
                                <div class="rounded-lg border border-dashed border-zinc-300 p-3 text-center dark:border-zinc-700">
                                    <flux:text size="sm" class="text-zinc-400">Secuencia paso a paso pendiente de carga.</flux:text>
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
