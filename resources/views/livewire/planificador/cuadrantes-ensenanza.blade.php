<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Cuadrantes de Enseñanza</flux:heading>
        <flux:text class="mt-1">Marco pedagógico ATA: responsabilidades del alumno y del instructor</flux:text>
    </div>

    @foreach ($cuadrantes as $cuadrante)
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ $cuadrante->etiqueta() }}</flux:heading>
                <flux:text size="sm" class="mt-0.5">{{ $cuadrante->descripcion() }}</flux:text>
            </div>

            <div class="grid gap-px bg-zinc-100 dark:bg-zinc-700 md:grid-cols-2">
                @foreach ($roles as $rol)
                    @php($items = $porCuadranteRol[$cuadrante->value][$rol->value] ?? collect())
                    @if ($items->isNotEmpty())
                        <div class="bg-white p-4 dark:bg-zinc-800">
                            <flux:text class="mb-2 block font-semibold {{ $rol->value === 'alumno' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                {{ $rol->etiqueta() }}
                            </flux:text>
                            <ol class="space-y-2">
                                @foreach ($items as $item)
                                    <li wire:key="ci-{{ $item->id }}" class="flex gap-2 text-sm">
                                        <span class="shrink-0 font-semibold text-zinc-400">{{ $loop->iteration }}.</span>
                                        <div class="min-w-0">
                                            <span class="text-zinc-800 dark:text-zinc-100">{{ $item->texto }}</span>
                                            @if ($item->detalle)
                                                <span x-data="{ open: false }">
                                                    <button type="button" x-on:click="open = !open" class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400" x-text="open ? '−' : '+'"></button>
                                                    <p x-show="open" x-cloak class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $item->detalle }}</p>
                                                </span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
