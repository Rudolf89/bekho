<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Formas Songahm</flux:heading>
        <flux:text class="mt-1">Secuencias del currículo ATA (poomsae), paso a paso. Fuente: Manual ATA Legacy.</flux:text>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="buscar" placeholder="Buscar forma…" icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:text size="sm" class="text-zinc-500">{{ $total }} {{ $total === 1 ? 'forma' : 'formas' }}</flux:text>
    </div>

    <div class="space-y-3">
        @forelse ($formas as $forma)
            <div wire:key="forma-{{ $forma->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:heading size="lg">{{ $forma->nombre }}</flux:heading>
                    <div class="flex items-center gap-2">
                        @if ($forma->grado)
                            <flux:badge size="sm" color="zinc">{{ $forma->grado->nombre }}</flux:badge>
                        @endif
                        <flux:badge size="sm" :color="$forma->verificado ? 'green' : 'amber'">
                            {{ $forma->verificado ? 'Verificada' : 'Sin secuencia (por confirmar)' }}
                        </flux:badge>
                    </div>
                </div>

                @if ($forma->significado)
                    <flux:text class="mt-1 italic text-zinc-500">{{ $forma->significado }}</flux:text>
                @endif

                @if ($forma->pasos->isNotEmpty())
                    <div x-data="{ open: false }" class="mt-3">
                        <button type="button" x-on:click="open = !open"
                                class="flex w-full items-center justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-left text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-100 dark:bg-zinc-900/50 dark:text-zinc-200 dark:hover:bg-zinc-900">
                            <span>Paso a paso · {{ $forma->pasos->count() }} movimientos</span>
                            <span class="text-red-600 dark:text-red-400" x-text="open ? '▾' : '▸'"></span>
                        </button>
                        <div x-show="open" x-cloak class="mt-2 max-h-[26rem] overflow-y-auto overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full min-w-[480px] text-left text-xs">
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
                                    @foreach ($forma->pasos as $paso)
                                        <tr class="odd:bg-white even:bg-zinc-50/60 dark:odd:bg-zinc-800 dark:even:bg-zinc-900/40">
                                            <td class="px-2 py-1.5 text-center font-semibold text-zinc-400">{{ $paso->numero }}</td>
                                            <td class="px-2 py-1.5 text-zinc-500">{{ $paso->lado ?: '—' }}</td>
                                            <td class="px-2 py-1.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $paso->tecnica }}</td>
                                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-300">{{ $paso->posicion?->nombre ?: '—' }}</td>
                                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-300">{{ $paso->seccion ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <flux:text size="sm" class="mt-3 text-zinc-500">El manual nombra esta forma pero no detalla su secuencia.</flux:text>
                @endif
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay formas cargadas.' }}</flux:text>
        @endforelse
    </div>
</div>
