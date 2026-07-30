@php
    $colores = [
        'sky' => ['pill' => 'bg-sky-600 text-white', 'ring' => 'ring-sky-500/40', 'head' => 'text-sky-700 dark:text-sky-400', 'bar' => 'bg-sky-500'],
        'lime' => ['pill' => 'bg-lime-600 text-white', 'ring' => 'ring-lime-500/40', 'head' => 'text-lime-700 dark:text-lime-400', 'bar' => 'bg-lime-500'],
        'orange' => ['pill' => 'bg-orange-600 text-white', 'ring' => 'ring-orange-500/40', 'head' => 'text-orange-700 dark:text-orange-400', 'bar' => 'bg-orange-500'],
    ];
    $c = $colores[$planificador['color']] ?? $colores['sky'];
@endphp

<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:heading size="xl">Class Planner</flux:heading>
        <flux:text class="mt-1">Planificadores de clase oficiales de BEKHO por nivel, con el detalle de cada área según los cuatro Cuadrantes de Enseñanza.</flux:text>
    </div>

    {{-- Selector de nivel --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($niveles as $clave => $info)
            @php($cc = $colores[$info['color']])
            <button
                type="button"
                wire:key="niv-{{ $clave }}"
                wire:click="$set('nivel', '{{ $clave }}')"
                class="rounded-full px-4 py-1.5 text-sm font-semibold ring-1 ring-inset transition-colors
                    {{ $clave === $nivelActual ? $cc['pill'].' '.$cc['ring'] : 'bg-white text-zinc-600 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-700/60' }}"
            >
                {{ $info['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Encabezado del nivel --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center gap-3">
            <span class="inline-block h-3 w-3 rounded-full {{ $c['bar'] }}"></span>
            <flux:heading size="lg" class="{{ $c['head'] }}">{{ $planificador['titulo'] }}</flux:heading>
        </div>
        <flux:text size="sm" class="mt-1">
            Cada área se trabaja a lo largo del ciclo según el examen: <span class="font-semibold">Estructura</span> (Examen 1),
            <span class="font-semibold">Emoción</span> (Examen 2), <span class="font-semibold">Conocimiento</span> (Examen 3) y
            <span class="font-semibold">Legado</span> (Examen 4).
        </flux:text>
    </div>

    {{-- Planificador: filas × cuadrantes --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[820px] border-collapse text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
                    <th class="w-48 px-3 py-2.5 font-semibold">Área</th>
                    <th class="px-3 py-2.5 font-semibold">Contenido</th>
                    @foreach ($cuadrantes as $i => $cuadrante)
                        <th class="w-40 px-3 py-2.5 font-semibold {{ $c['head'] }}">
                            {{ $cuadrante }}
                            <span class="block text-[10px] font-normal text-zinc-400">Examen {{ $i + 1 }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($planificador['filas'] as $fila)
                    <tr class="align-top odd:bg-white even:bg-zinc-50/60 dark:odd:bg-zinc-800 dark:even:bg-zinc-900/40" wire:key="fila-{{ $loop->index }}">
                        <td class="px-3 py-2.5 font-semibold text-zinc-800 dark:text-zinc-100">{{ $fila['area'] }}</td>
                        <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $fila['general'] ?? '—' }}</td>
                        @if ($fila['cuadrantes'])
                            @foreach ($fila['cuadrantes'] as $celda)
                                <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $celda }}</td>
                            @endforeach
                        @else
                            <td class="px-3 py-2.5 text-center text-zinc-300" colspan="{{ count($cuadrantes) }}">Transversal a los cuatro exámenes</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Diferencias de programas --}}
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($diferencias as $programa => $puntos)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800" wire:key="prog-{{ $loop->index }}">
                <flux:heading size="sm">{{ $programa }}</flux:heading>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($puntos as $punto)
                        <li class="flex gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <span class="text-red-500">•</span>
                            <span>{{ $punto }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
