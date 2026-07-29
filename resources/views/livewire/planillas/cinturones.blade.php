@php
    $hex = [
        'Blanco' => '#f5f5f4', 'Naranjo' => '#f97316', 'Amarillo' => '#eab308',
        'Camuflado' => '#4d7c0f', 'Verde' => '#16a34a', 'Púrpura' => '#7c3aed',
        'Azul' => '#2563eb', 'Café' => '#78350f', 'Rojo' => '#dc2626',
        'Rojo/Negro' => 'linear-gradient(#18181b 50%, #dc2626 50%)', 'Negro' => '#18181b',
    ];
    $tipoColor = ['recomendado' => 'sky', 'decidido' => 'indigo', 'dan' => 'zinc'];
@endphp

<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Cinturones</flux:heading>
        <flux:text class="mt-1">Escala de grados: color, tipo, franjas, significado (filosofía Songahm) y técnicas.</flux:text>
    </div>

    {{-- Selector de escala --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($escalas as $e)
            <flux:button
                wire:key="esc-{{ $e->value }}"
                wire:click="$set('escala', '{{ $e->value }}')"
                size="sm"
                :variant="$e === $escalaActual ? 'primary' : 'filled'"
            >
                {{ $e->etiqueta() }}
            </flux:button>
        @endforeach
    </div>

    <div class="space-y-3">
        @foreach ($grados as $grado)
            <div wire:key="g-{{ $grado->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start gap-3">
                    {{-- Muestra de color con franjas --}}
                    <div class="mt-1 shrink-0">
                        {{-- Cinturón: color (o gradiente Rojo/Negro / negro del dan) con
                             franjas rojas (danes 1-4) o estrellas doradas (danes 5+) dentro. --}}
                        <div class="relative h-10 w-14 overflow-hidden rounded border border-zinc-300 dark:border-zinc-600"
                             style="background: {{ $hex[$grado->color] ?? '#a1a1aa' }}">
                            @if ($grado->franjas > 0)
                                <div class="absolute inset-x-1 bottom-1 flex flex-col gap-1">
                                    @for ($i = 0; $i < $grado->franjas; $i++)
                                        <span class="h-1.5 w-full rounded-sm bg-red-600"></span>
                                    @endfor
                                </div>
                            @endif
                            @if ($grado->estrellas > 0)
                                <div class="absolute inset-0 flex items-center justify-center gap-0.5 text-[10px] leading-none text-amber-400">
                                    @for ($i = 0; $i < $grado->estrellas; $i++)★@endfor
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg">{{ $grado->orden }}. {{ $grado->nombre }}</flux:heading>
                            @if ($grado->tipo->value !== 'base')
                                <flux:badge size="sm" :color="$tipoColor[$grado->tipo->value] ?? 'zinc'">{{ $grado->tipo->etiqueta() }}</flux:badge>
                            @endif
                            @if ($grado->franjas > 0)
                                <flux:badge size="sm" color="red">{{ $grado->franjas }} {{ $grado->franjas === 1 ? 'franja' : 'franjas' }}</flux:badge>
                            @endif
                            @if ($grado->estrellas > 0)
                                <flux:badge size="sm" color="amber">{{ $grado->estrellas }} {{ $grado->estrellas === 1 ? 'estrella' : 'estrellas' }}</flux:badge>
                            @endif
                        </div>

                        @if ($grado->significado)
                            <flux:text size="sm" class="mt-1 block italic text-zinc-500">{{ $grado->significado }}</flux:text>
                        @endif

                        @if ($grado->tecnicas->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($grado->tecnicas as $tecnica)
                                    <flux:badge size="sm" color="{{ $tecnica->core ? 'green' : 'amber' }}">{{ $tecnica->nombre }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
