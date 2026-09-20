<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Recompensas</flux:heading>
        <flux:text class="mt-1">Otorga franjas, estrellas y coleccionables a los alumnos.</flux:text>
    </div>

    {{-- Selección de alumno --}}
    <flux:select wire:model.live="matriculaId" label="Alumno" placeholder="Elige un alumno…">
        <flux:select.option value="">Elige un alumno…</flux:select.option>
        @foreach ($matriculas as $m)
            <flux:select.option value="{{ $m->id }}">{{ $m->persona?->nombreCompleto() }} · {{ $m->grupo_etario?->etiqueta() }}</flux:select.option>
        @endforeach
    </flux:select>

    @if (! $matricula)
        <flux:callout icon="gift">Elige un alumno para ver y otorgar sus recompensas.</flux:callout>
    @else
        @foreach ($tipos as $tipo)
            @php($items = $recompensasPorTipo[$tipo->value] ?? collect())
            @if ($items->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-3">
                        <flux:heading size="lg">{{ $tipo->etiqueta() }}</flux:heading>
                        <flux:text size="sm" class="mt-0.5">{{ $tipo->descripcion() }}</flux:text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($items as $recompensa)
                            @php($ganadas = (int) ($conteo[$recompensa->id] ?? 0))
                            <div wire:key="rec-{{ $recompensa->id }}"
                                 class="flex items-center gap-3 rounded-lg border p-3 {{ $ganadas > 0 ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xl {{ $ganadas > 0 ? '' : 'opacity-40 grayscale' }}"
                                      style="background: {{ $recompensa->color ?? '#e4e4e7' }}22">
                                    {{ $recompensa->emoji ?? '🏅' }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <flux:text class="font-semibold">{{ $recompensa->nombre }}</flux:text>
                                    @if ($recompensa->repetible && $ganadas > 0)
                                        <flux:badge size="sm" color="amber" class="ml-1">×{{ $ganadas }}</flux:badge>
                                    @elseif ($ganadas > 0)
                                        <flux:badge size="sm" color="green" class="ml-1">Ganada</flux:badge>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    @if ($ganadas > 0)
                                        <flux:button wire:click="quitar({{ $recompensa->id }})" icon="minus" size="xs" variant="subtle" />
                                    @endif
                                    @if ($recompensa->repetible || $ganadas === 0)
                                        <flux:button wire:click="otorgar({{ $recompensa->id }})" icon="plus" size="xs" variant="primary" />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>
