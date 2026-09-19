<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Mis logros</flux:heading>
        <flux:text class="mt-1">Recompensas ganadas y coleccionables por conseguir.</flux:text>
    </div>

    @if ($coleccion->isEmpty())
        <flux:callout icon="gift">Aún no hay alumnos asociados a tu cuenta.</flux:callout>
    @endif

    @foreach ($coleccion as $fila)
        @php($matricula = $fila['matricula'])
        <div wire:key="mat-{{ $matricula->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ $matricula->persona?->nombreCompleto() }}</flux:heading>
                    <flux:text size="sm" class="mt-0.5">{{ $matricula->grupo_etario?->etiqueta() }}</flux:text>
                </div>
                <flux:badge size="lg" color="{{ $fila['total'] > 0 ? 'green' : 'zinc' }}">
                    {{ $fila['total'] }} {{ $fila['total'] === 1 ? 'logro' : 'logros' }}
                </flux:badge>
            </div>

            @foreach ($tipos as $tipo)
                @php($items = $fila['recompensas'][$tipo->value] ?? collect())
                @if ($items->isNotEmpty())
                    <div class="mb-4 last:mb-0">
                        <flux:text size="sm" class="mb-2 block font-semibold text-zinc-500">{{ $tipo->etiqueta() }}</flux:text>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($items as $recompensa)
                                @php($n = (int) ($fila['ganados'][$recompensa->id] ?? 0))
                                <div wire:key="mat-{{ $matricula->id }}-r-{{ $recompensa->id }}"
                                     class="flex w-24 flex-col items-center gap-1 text-center"
                                     title="{{ $recompensa->nombre }}{{ $n > 0 ? '' : ' (por conseguir)' }}">
                                    <span class="relative flex size-14 items-center justify-center rounded-full text-2xl {{ $n > 0 ? 'ring-2 ring-green-400 dark:ring-green-600' : 'opacity-40 grayscale' }}"
                                          style="background: {{ $recompensa->color ?? '#e4e4e7' }}22">
                                        {{ $n > 0 ? ($recompensa->emoji ?? '🏅') : '🔒' }}
                                        @if ($recompensa->repetible && $n > 0)
                                            <span class="absolute -bottom-1 -right-1 rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white">×{{ $n }}</span>
                                        @endif
                                    </span>
                                    <flux:text size="xs" class="leading-tight {{ $n > 0 ? '' : 'text-zinc-400' }}">{{ $recompensa->nombre }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach
</div>
