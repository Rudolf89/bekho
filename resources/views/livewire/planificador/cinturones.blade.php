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

    {{-- Leyenda de variantes de patada --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
        <p>Cada patada se ejecuta en <span class="font-semibold">4 variantes</span>: <span class="font-semibold">1</span> pierna delantera · <span class="font-semibold">2</span> pierna trasera · <span class="font-semibold">3</span> con paso, pierna delantera · <span class="font-semibold">4</span> con paso, pierna trasera.</p>
        <p class="mt-1"><span class="font-semibold text-red-700 dark:text-red-400">BEKHO</span> es lo que se rinde en examen; <span class="font-semibold text-sky-700 dark:text-sky-400">ATA</span> es la referencia del manual.</p>
        <p class="mt-1 text-zinc-500 dark:text-zinc-400">Los 9 grados de color (Blanco a Rojo) tienen sus patadas BEKHO dictadas por la escuela, grado por grado.</p>
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
                    <div class="mt-0.5 shrink-0">
                        {{-- Cinturón: color (o gradiente Rojo/Negro / negro del dan) con
                             franjas amarillas (danes 1-4) o estrellas amarillas (danes 5+). --}}
                        <div class="relative flex h-12 w-16 flex-col items-center justify-center gap-[3px] overflow-hidden rounded-md border border-black/10 px-2 shadow-sm ring-1 ring-inset ring-white/10"
                             style="background: {{ $hex[$grado->color] ?? '#a1a1aa' }}">
                            @if ($grado->franjas > 0)
                                @for ($i = 0; $i < $grado->franjas; $i++)
                                    <span class="h-[5px] w-full rounded-full bg-yellow-400 shadow-[0_1px_1px_rgba(0,0,0,.35)]"></span>
                                @endfor
                            @elseif ($grado->estrellas > 0)
                                <div class="flex gap-0.5 text-sm leading-none text-yellow-400 [text-shadow:0_1px_1px_rgba(0,0,0,.4)]">
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
                                <flux:badge size="sm" color="yellow">{{ $grado->franjas }} {{ $grado->franjas === 1 ? 'franja' : 'franjas' }}</flux:badge>
                            @endif
                            @if ($grado->estrellas > 0)
                                <flux:badge size="sm" color="yellow">{{ $grado->estrellas }} {{ $grado->estrellas === 1 ? 'estrella' : 'estrellas' }}</flux:badge>
                            @endif
                        </div>

                        @if ($grado->significado)
                            <flux:text size="sm" class="mt-1 block italic text-zinc-500">{{ $grado->significado }}</flux:text>
                        @endif

                        @php
                            $patadas = $grado->tecnicas->filter(fn ($t) => $t->categoria->value === 'patada');
                            $bekho = $patadas->where('fuente', 'bekho')->values();
                            $ata = $patadas->where('fuente', 'ata')->values();
                            $otras = $grado->tecnicas->filter(fn ($t) => $t->categoria->value !== 'patada')->values();
                        @endphp

                        {{-- Comparativa de patadas: BEKHO (examen) vs ATA (manual) --}}
                        @if ($bekho->isNotEmpty() || $ata->isNotEmpty())
                            <div class="mt-3">
                                <flux:text size="sm" class="mb-1.5 block font-semibold text-zinc-600 dark:text-zinc-300">Patadas del grado</flux:text>
                                <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-zinc-200 bg-zinc-200 text-xs dark:border-zinc-700 dark:bg-zinc-700">
                                    @foreach ([['BEKHO · examen', $bekho, 'text-red-700 dark:text-red-400'], ['ATA · manual', $ata, 'text-sky-700 dark:text-sky-400']] as [$titulo, $lista, $color])
                                        <div class="bg-white p-2.5 dark:bg-zinc-800">
                                            <div class="mb-1 font-semibold {{ $color }}">{{ $titulo }}</div>
                                            @if ($lista->isEmpty())
                                                <div class="text-zinc-400">— pendiente</div>
                                            @else
                                                <ul class="space-y-1">
                                                    @foreach ($lista as $t)
                                                        <li class="text-zinc-700 dark:text-zinc-200">
                                                            {{ $t->nombre }}
                                                            @if ($t->descripcion)
                                                                <span class="block text-zinc-400">{{ \Illuminate\Support\Str::after($t->descripcion, 'Ejecuciones: ') }}</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Otras técnicas del grado (formas, armas, etc.) --}}
                        @if ($otras->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($otras as $tecnica)
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
