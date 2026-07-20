<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:heading size="xl">Ciclos</flux:heading>
        <flux:text class="mt-1">Class planners del currículo ATA: cada ciclo es una Habilidad para la Vida de 8 semanas</flux:text>
    </div>

    {{-- Selector de ciclo --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($ciclos as $c)
            <flux:button
                wire:key="ciclo-{{ $c->id }}"
                wire:click="$set('cicloId', {{ $c->id }})"
                size="sm"
                :variant="$ciclo && $c->id === $ciclo->id ? 'primary' : 'filled'"
            >
                {{ $c->orden }}. {{ $c->habilidad_vida->etiqueta() }}
            </flux:button>
        @endforeach
    </div>

    @if ($ciclo)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ $ciclo->nombre ?: $ciclo->habilidad_vida->etiqueta() }}</flux:heading>
            <flux:text size="sm" class="mt-0.5">Ciclo {{ $ciclo->orden }} · {{ $ciclo->semanas }} semanas</flux:text>
        </div>

        {{-- Grilla del class planner --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="w-44 p-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Área</th>
                        @foreach ($bloques as $bloque)
                            <th class="p-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">Sem {{ $bloque }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr wire:key="fila-{{ $fila->value }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-700/60">
                            <th class="p-3 text-left align-top font-semibold text-zinc-600 dark:text-zinc-300">{{ $fila->etiqueta() }}</th>
                            @foreach ($bloques as $bloque)
                                <td class="p-3 align-top text-zinc-700 dark:text-zinc-200">
                                    {{ $celdas[$fila->value][$bloque][0]->contenido ?? '—' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Lecciones de vida del ciclo --}}
        @if ($ciclo->lecciones->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">Lecciones de vida — {{ $ciclo->habilidad_vida->etiqueta() }}</flux:heading>
                <div class="space-y-3">
                    @foreach ($ciclo->lecciones as $leccion)
                        <div wire:key="lv-{{ $leccion->id }}" class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-700/60">
                            <flux:text class="font-semibold">Semana {{ $leccion->semana }}</flux:text>
                            <div class="mt-2 grid gap-3 md:grid-cols-3">
                                @foreach ($leccion->momentos() as $momento)
                                    <div>
                                        <flux:text size="xs" class="block font-semibold uppercase tracking-wide text-zinc-400">{{ $momento['etiqueta'] }}</flux:text>
                                        @if ($momento['texto'])
                                            <flux:text size="sm" class="mt-1 block text-zinc-700 dark:text-zinc-200">{{ $momento['texto'] }}</flux:text>
                                        @endif
                                        @if ($momento['frase'])
                                            <flux:text size="sm" class="mt-1 block italic text-red-700 dark:text-red-400">“{{ $momento['frase'] }}”</flux:text>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
