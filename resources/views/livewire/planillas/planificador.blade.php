@php($nivelActivo = $nivel)
<div class="mx-auto w-full max-w-5xl space-y-5">
    <div class="text-center">
        <flux:heading size="xl">Planificador</flux:heading>
        <flux:text class="mt-1">Todos los niveles · 45 minutos · adaptado por grupo etario</flux:text>
    </div>

    {{-- Selector de nivel --}}
    <div class="flex flex-wrap justify-center gap-2">
        @foreach ($niveles as $n)
            <flux:button size="sm" wire:click="$set('nivel', '{{ $n['valor'] }}')"
                :variant="$nivelActivo === $n['valor'] ? 'primary' : 'filled'">
                {{ $n['etiqueta'] }}
            </flux:button>
        @endforeach
    </div>

    {{-- Semanas de Cinturón Negro --}}
    @if ($esBlackBelt && isset($semanasBB))
        <div class="flex flex-wrap justify-center gap-2">
            @foreach ($semanasBB as $sem)
                <flux:button size="sm" wire:click="$set('bbSemana', '{{ $sem->clave }}')"
                    :variant="$bbSemana === $sem->clave ? 'primary' : 'filled'">
                    {{ $sem->icono }} {{ $sem->label }}
                </flux:button>
            @endforeach
        </div>
    @endif

    {{-- Selector de grupo etario --}}
    <div class="flex flex-wrap justify-center gap-2">
        @foreach ($grupos as $g)
            <flux:button size="sm" wire:click="$set('grupo', '{{ $g->value }}')"
                :variant="$grupo === $g->value ? 'primary' : 'filled'">
                {{ $g->etiqueta() }} <span class="ml-1 opacity-70">({{ $g->rangoEdad() }})</span>
            </flux:button>
        @endforeach
    </div>

    {{-- Pestañas --}}
    <div class="flex overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        @foreach (['planner' => '📋 Planner', 'warmup' => '🔥 Calentamiento', 'leccion' => '📖 Lección de Vida'] as $key => $lbl)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 px-3 py-2.5 text-sm font-semibold transition
                {{ $tab === $key ? 'bg-red-600 text-white' : 'bg-white text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                {{ $lbl }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════ PLANNER (regular) ═══════════ --}}
    @if ($tab === 'planner' && ! $esBlackBelt)
        @if ($planilla)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text class="font-semibold">{{ $planilla->nombre }}</flux:text>
                <div class="flex items-center gap-2">
                    @if ($curriculo)
                        <flux:badge color="zinc" size="sm">Defensa: {{ $curriculo->defensa }}</flux:badge>
                    @endif
                    <flux:badge color="red" size="sm">⏱ 45 min</flux:badge>
                </div>
            </div>

            {{-- ─── Rotación del ciclo: qué contenido toca esta semana ─── --}}
            @isset($cicloActual)
                @php($filaIcono = [
                    'calentamiento' => '🔥', 'patadas' => '🦵', 'formas' => '⭐',
                    'cuadrantes' => '🧩', 'protech' => '🏹', 'drills_parejas' => '🥊',
                ])
                <div class="rounded-xl border border-red-200 bg-red-50/60 p-4 dark:border-red-500/30 dark:bg-red-950/20">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:text class="font-semibold text-red-700 dark:text-red-300">🔄 Rotación del ciclo · qué toca esta semana</flux:text>
                        <flux:text size="sm" class="text-zinc-500">El plan de abajo es la estructura fija; esto es el contenido que rota.</flux:text>
                    </div>

                    {{-- Selector de ciclo (Habilidad para la Vida) --}}
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($ciclos as $c)
                            <flux:button size="xs" wire:click="$set('cicloId', {{ $c->id }})"
                                :variant="$cicloActual->id === $c->id ? 'primary' : 'filled'">
                                {{ $c->orden }}. {{ $c->habilidad_vida->etiqueta() }}
                            </flux:button>
                        @endforeach
                    </div>

                    {{-- Selector de bloque de semanas --}}
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <flux:text size="sm" class="mr-1 text-zinc-500">Semanas:</flux:text>
                        @foreach ($bloquesCiclo as $blo)
                            <flux:button size="xs" wire:click="$set('bloque', '{{ $blo }}')"
                                :variant="$bloqueActual === $blo ? 'primary' : 'filled'">
                                {{ $blo }}
                            </flux:button>
                        @endforeach
                    </div>

                    {{-- Grilla fila × contenido del bloque --}}
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($filasCiclo as $fila)
                            <div class="rounded-lg border border-zinc-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-800" wire:key="rot-{{ $fila->value }}">
                                <div class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $filaIcono[$fila->value] ?? '•' }} {{ $fila->etiqueta() }}</div>
                                <div class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $rotacion[$fila->value]?->contenido ?? '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endisset

            {{-- Combinaciones de patadas del nivel --}}
            @if ($curriculo && filled($curriculo->combinaciones))
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="mb-2 block font-semibold">🦵 Combinaciones de Patadas — {{ $planilla->nivel->etiqueta() }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($curriculo->combinaciones as $i => $combo)
                            <span class="rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium dark:border-zinc-600 dark:bg-zinc-700">
                                {{ $i + 1 }}. {{ $combo }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Tabla de bloques --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="whitespace-nowrap px-3 py-2.5 font-semibold text-zinc-500">⏱ Tiempo</th>
                            <th class="px-3 py-2.5 font-semibold text-zinc-500">Bloque</th>
                            <th class="px-3 py-2.5 font-semibold text-zinc-500">Actividad y detalle</th>
                            <th class="px-3 py-2.5 font-semibold text-zinc-500">Cuadrante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($planilla->bloques as $b)
                            <tr class="border-b border-zinc-100 align-top last:border-0 dark:border-zinc-700/60">
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-zinc-700 dark:text-zinc-200">{{ $b->tiempo }}</td>
                                <td class="px-3 py-3 font-semibold text-zinc-800 dark:text-zinc-100">{{ $b->tituloVisible() }}</td>
                                <td class="whitespace-pre-line px-3 py-3 leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $b->contenido }}</td>
                                <td class="px-3 py-3">
                                    @if ($b->cuadrante_texto)
                                        <span class="inline-block whitespace-nowrap rounded-md px-2 py-1 text-xs font-semibold text-white"
                                            style="background-color: {{ $b->cuadrante_color ?? '#6c757d' }}">
                                            {{ $b->cuadrante_texto }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <flux:text>No hay una planilla cargada para este grupo y nivel en la academia activa.</flux:text>
            </div>
        @endif
    @endif

    {{-- ═══════════ PLANNER (Cinturón Negro) ═══════════ --}}
    @if ($tab === 'planner' && $esBlackBelt)
        @if (isset($bb) && $bb)
            <div class="rounded-xl p-4 text-white" style="background-color: {{ $bb->color ?? '#1a1a2e' }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-lg font-bold">{{ $bb->icono }} {{ $bb->label }}</span>
                    <span class="rounded-lg bg-white/15 px-3 py-1 text-sm font-semibold">Tema: {{ $bb->tema }}</span>
                </div>
            </div>

            @php($adapt = $bb->adaptacionDe($grupo))
            @if ($adapt)
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-500/40 dark:bg-amber-950/20">
                    <span class="font-semibold">💡 Adaptación {{ $grupoEnum->etiqueta() }}:</span>
                    <span class="text-zinc-700 dark:text-zinc-300">{{ $adapt }}</span>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                @php($secciones = [
                    'warmup_general' => '🔥 Warm Up General',
                    'warmup_especifico' => '🎯 Warm Up Específico',
                    'basicos' => '⭐ Básicos / Fórmulas / Patadas',
                    'sparring' => '⚔️ Sparring / Combat Weapon',
                    'anuncios' => '📋 Anuncios',
                ])
                @foreach ($secciones as $clave => $titulo)
                    @php($items = $bb->itemsDe($clave))
                    @if (count($items))
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="rounded-t-xl px-4 py-2 text-sm font-semibold text-white"
                                style="background-color: {{ $clave === 'anuncios' ? '#6c757d' : ($bb->color ?? '#1a1a2e') }}">
                                {{ $titulo }}
                            </div>
                            <ol class="space-y-1.5 p-4 text-sm text-zinc-600 dark:text-zinc-300">
                                @foreach ($items as $i => $item)
                                    <li class="flex gap-2">
                                        <span class="font-semibold text-zinc-400">{{ $i + 1 }}.</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    @endif

    {{-- ═══════════ CALENTAMIENTO ═══════════ --}}
    @if ($tab === 'warmup')
        @if ($nota)
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/40">
                <span class="font-semibold">💡 Nota para {{ $grupoEnum->etiqueta() }}:</span>
                <span class="text-zinc-700 dark:text-zinc-300">{{ $nota->nota }}</span>
            </div>
        @endif

        {{-- La rutina de calentamiento se guarda en una clase del horario. --}}
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="font-medium">Guardar en la clase:</flux:text>
            <flux:select wire:model.live="claseId" placeholder="Elige una clase" class="max-w-xs">
                @forelse ($clases as $c)
                    <flux:select.option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->grupo_etario->etiqueta() }})</flux:select.option>
                @empty
                    <flux:select.option value="" disabled>No hay clases activas en esta academia</flux:select.option>
                @endforelse
            </flux:select>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Catálogo de categorías --}}
            <div class="space-y-3 lg:col-span-2">
                @foreach ($categorias as $cat)
                    <div class="overflow-hidden rounded-xl border" style="border-color: {{ $cat->color }}" x-data="{ open: false }" wire:key="cat-{{ $cat->id }}">
                        <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left font-semibold text-white"
                            style="background-color: {{ $cat->color }}">
                            <span>{{ $cat->nombre }}</span>
                            <span x-text="open ? '▲' : '▼'"></span>
                        </button>
                        <div x-show="open" x-cloak class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($cat->ejercicios as $ej)
                                @php($sel = in_array((string) $ej->id, $seleccion, true))
                                <button type="button" wire:click="alternarEjercicio({{ $ej->id }})"
                                    class="flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-700/40 {{ $sel ? 'bg-zinc-50 dark:bg-zinc-700/30' : 'bg-white dark:bg-zinc-800' }}">
                                    <span class="mt-0.5 shrink-0 text-base">{{ $sel ? '✅' : '⬜' }}</span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold" style="color: {{ $cat->color }}">{{ $ej->nombre }}</span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $ej->descripcion }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mi Rutina --}}
            <div class="lg:sticky lg:top-4 lg:self-start">
                <div class="flex items-center justify-between rounded-t-xl bg-zinc-800 px-4 py-2.5 text-white dark:bg-zinc-700">
                    <span class="font-semibold">📋 Mi Rutina ({{ $seleccionados->count() }})</span>
                    @if ($seleccionados->isNotEmpty())
                        <flux:button size="xs" variant="danger" wire:click="limpiarCalentamiento">Limpiar</flux:button>
                    @endif
                </div>
                <div class="space-y-2 rounded-b-xl border border-t-0 border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                    @forelse ($seleccionados as $i => $ej)
                        <div class="flex items-start gap-2 rounded-lg border bg-white p-2 dark:bg-zinc-800" style="border-color: {{ $ej->categoria?->color ?? '#ccc' }}" wire:key="sel-{{ $ej->id }}">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background-color: {{ $ej->categoria?->color ?? '#999' }}">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold" style="color: {{ $ej->categoria?->color ?? '#333' }}">{{ $ej->nombre }}</p>
                                <p class="text-xs text-zinc-500">{{ $ej->descripcion }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs text-zinc-400">Toca un ejercicio para agregarlo aquí.</p>
                    @endforelse

                    @if ($seleccionados->isNotEmpty())
                        <div class="rounded-lg bg-green-50 p-2 text-center text-xs font-semibold text-green-700 dark:bg-green-950/30 dark:text-green-300">
                            ⏱ Tiempo estimado: {{ $seleccionados->count() * 2 }}–{{ $seleccionados->count() * 3 }} min
                        </div>
                        <flux:button class="w-full" variant="primary" icon="bookmark" wire:click="guardarCalentamiento"
                            :disabled="! $puedeGuardar">
                            Guardar en la clase
                        </flux:button>
                        @unless ($puedeGuardar)
                            <flux:text size="sm" class="text-center text-zinc-400">Elige una clase arriba para guardar la rutina.</flux:text>
                        @endunless
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════ LECCIÓN DE VIDA ═══════════ --}}
    @if ($tab === 'leccion')
        {{-- Selector de ciclo (mismo eje que la rotación del Planner) --}}
        @isset($ciclos)
            <div class="flex flex-wrap justify-center gap-1.5">
                @foreach ($ciclos as $c)
                    <flux:button size="xs" wire:click="$set('cicloId', {{ $c->id }})"
                        :variant="$cicloActual && $cicloActual->id === $c->id ? 'primary' : 'filled'">
                        {{ $c->orden }}. {{ $c->habilidad_vida->etiqueta() }}
                    </flux:button>
                @endforeach
            </div>
        @endisset

        <div class="flex flex-wrap justify-center gap-2">
            @forelse ($lecciones as $lec)
                <flux:button size="sm" wire:click="$set('lecSemana', {{ $lec->semana }})"
                    :variant="$lecSemana === $lec->semana ? 'primary' : 'filled'">
                    Semana {{ $lec->semana }}
                </flux:button>
            @empty
                <flux:text size="sm" class="text-zinc-400">Este ciclo aún no tiene lecciones cargadas.</flux:text>
            @endforelse
        </div>

        @if ($leccion)
            <div class="rounded-xl bg-zinc-900 p-5 text-center text-white">
                <p class="text-xs uppercase tracking-widest text-white/60">Lección de Vida · ATA Legacy</p>
                <h2 class="mt-1 text-2xl font-bold">📖 {{ $leccion->habilidad->etiqueta() }}</h2>
                <p class="mt-1 text-sm text-white/70">Semana {{ $leccion->semana }}</p>
            </div>

            <div class="space-y-3">
                @foreach ($leccion->momentos() as $m)
                    @php($colores = ['comienzo' => '#2d6a4f', 'durante' => '#f77f00', 'fin' => '#c1440e'])
                    @php($iconos = ['comienzo' => '🟢', 'durante' => '🟡', 'fin' => '🔴'])
                    <div class="overflow-hidden rounded-xl border-2" style="border-color: {{ $colores[$m['clave']] }}">
                        <div class="px-4 py-2.5 font-semibold text-white" style="background-color: {{ $colores[$m['clave']] }}">
                            {{ $iconos[$m['clave']] }} {{ $m['etiqueta'] }}
                        </div>
                        <div class="space-y-3 bg-white p-4 dark:bg-zinc-800">
                            <p class="leading-relaxed text-zinc-700 dark:text-zinc-200">{{ $m['texto'] }}</p>
                            @if ($m['frase'])
                                <div class="rounded-lg border px-4 py-2.5 text-center font-bold" style="border-color: {{ $colores[$m['clave']] }}; color: {{ $colores[$m['clave']] }}">
                                    “{{ $m['frase'] }}”
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl bg-zinc-50 p-3 text-center text-xs text-zinc-500 dark:bg-zinc-900/40">
                💡 La Lección de Vida se presenta en 3 momentos clave de la clase. Adapta el lenguaje según el grupo etario.
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <flux:text>Aún no hay lecciones cargadas.</flux:text>
            </div>
        @endif
    @endif
</div>
