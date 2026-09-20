<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Reportes</flux:heading>
        <flux:text class="mt-1">Progreso de la red: distribución, altas y comparativa por sede.</flux:text>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text size="sm" class="text-zinc-500">Alumnos activos</flux:text>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ number_format($alumnosActivos, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text size="sm" class="text-zinc-500">Altas este mes</flux:text>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ $altasMes }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text size="sm" class="text-zinc-500">Morosos</flux:text>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ $morosos }}</div>
        </div>
    </div>

    {{-- Distribución por cinturón --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Distribución por cinturón</flux:heading>
        @forelse ($distribucion as $d)
            <div wire:key="dist-{{ $loop->index }}" class="mb-2 flex items-center gap-3">
                <div class="w-32 shrink-0 truncate text-sm">{{ $d['nombre'] }}</div>
                <div class="h-4 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded bg-[#b01e28]" style="width: {{ (int) round($d['total'] / $maxDistribucion * 100) }}%"></div>
                </div>
                <div class="w-10 shrink-0 text-right text-sm tabular-nums">{{ $d['total'] }}</div>
            </div>
        @empty
            <flux:text class="text-zinc-500">Sin alumnos activos.</flux:text>
        @endforelse
    </div>

    {{-- Altas por mes --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Altas por mes · últimos 12</flux:heading>
        <div class="flex items-end gap-2" style="height: 140px">
            @foreach ($altas as $a)
                <div wire:key="alta-{{ $loop->index }}" class="flex flex-1 flex-col items-center justify-end gap-1">
                    <div class="w-full rounded-t bg-[#b01e28]" style="height: {{ (int) round($a['total'] / $maxAltas * 110) }}px" title="{{ $a['total'] }}"></div>
                    <div class="text-xs text-zinc-400">{{ $a['mes'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Comparativa de sedes --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between gap-4">
            <flux:heading size="lg">Comparativa de sedes</flux:heading>
            <flux:button wire:click="exportarCsv" size="sm" variant="ghost" icon="arrow-down-tray">Descargar CSV</flux:button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="py-2 pr-3">Sede</th>
                        <th class="py-2 pr-3">Activos</th>
                        <th class="py-2 pr-3">Morosos</th>
                        <th class="py-2 pr-3">Cobrado del mes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comparativa as $c)
                        <tr wire:key="cmp-{{ $loop->index }}" class="border-b border-zinc-100 dark:border-zinc-700/60">
                            <td class="py-2 pr-3 font-medium">{{ $c['sede'] }}</td>
                            <td class="py-2 pr-3 tabular-nums">{{ $c['activos'] }}</td>
                            <td class="py-2 pr-3 tabular-nums">{{ $c['morosos'] }}</td>
                            <td class="py-2 pr-3 tabular-nums">${{ number_format($c['cobrado'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-zinc-500">No hay sedes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
