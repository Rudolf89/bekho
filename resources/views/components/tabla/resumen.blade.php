@props([
    // Cantidad de filas mostradas (tras aplicar el filtro).
    'total' => 0,
    // Sustantivo para el conteo (singular); se pluraliza en español.
    'etiqueta' => 'registro',
    // Sumas de montos: lista de ['etiqueta' => 'Total', 'valor' => '$123.456'].
    'sumas' => [],
])

@php
    $plural = $total === 1 ? $etiqueta : \Illuminate\Support\Str::plural($etiqueta);
@endphp

{{-- Barra de resumen de la tabla: conteo de filas + sumas de montos. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-800/50']) }}>
    <span class="text-zinc-500 dark:text-zinc-400">
        <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($total, 0, ',', '.') }}</span>
        {{ $plural }}
    </span>

    @if (! empty($sumas))
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
            @foreach ($sumas as $suma)
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $suma['etiqueta'] }}:
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $suma['valor'] }}</span>
                </span>
            @endforeach
        </div>
    @endif
</div>
