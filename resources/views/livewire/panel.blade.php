<div class="mx-auto w-full max-w-6xl space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b01e28]">Panel del maestro</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Resumen</h1>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
            <flux:icon.building-office-2 class="size-4 text-zinc-400" />
            {{ $chip }}
        </span>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <flux:icon.users class="size-4" /> Alumnos activos
            </div>
            <div class="mt-3 text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ $alumnosActivos }}</div>
            <div class="mt-1 text-sm text-zinc-500">+{{ $alumnosNuevosMes }} este mes</div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <flux:icon.clipboard-document-check class="size-4" /> Asistencia hoy
            </div>
            <div class="mt-3 text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                {{ $presentesHoy }}<span class="text-2xl text-zinc-400">/{{ $esperadosHoy }}</span>
            </div>
            <div class="mt-1 text-sm text-zinc-500">{{ $clasesHoy->count() }} clases en agenda</div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <flux:icon.banknotes class="size-4" /> Pagos de {{ now()->translatedFormat('F') }}
            </div>
            <div class="mt-3 text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ $pagosMes }}</div>
            <div class="mt-1 text-sm {{ $porCobrar > 0 ? 'text-amber-600' : 'text-zinc-500' }}">{{ $porCobrar }} por cobrar</div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <flux:icon.trophy class="size-4" /> Próximo examen
            </div>
            <div class="mt-3 text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                {{ $proximaConvocatoria ? $proximaConvocatoria->fecha->translatedFormat('d M') : '—' }}
            </div>
            <div class="mt-1 text-sm text-zinc-500">
                {{ $proximaConvocatoria ? $proximaConvocatoria->inscripciones_count.' inscritos' : 'sin convocatoria' }}
            </div>
        </div>
    </div>

    {{-- Cuerpo: clases de hoy + columna lateral --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Clases de hoy --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Clases de hoy</h2>
                @if ($puedeAsistencia)
                    <a href="{{ route('asistencia.tomar') }}" wire:navigate class="text-sm font-semibold text-[#b01e28] hover:underline">Tomar asistencia →</a>
                @endif
            </div>

            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($clasesHoy as $clase)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="shrink-0 font-mono text-sm font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $clase->hora_hoy }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $clase->grupo_etario->etiqueta() }}</p>
                                <p class="truncate text-sm text-zinc-500">
                                    {{ $clase->sede?->nombre ?? 'Sede' }}@if ($clase->planificacion?->habilidad_vida) · Habilidad de hoy: {{ $clase->planificacion->habilidad_vida->etiqueta() }}@endif
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm text-zinc-500">{{ $clase->matriculasEsperadas()->count() }} alumnos</p>
                            @if ($clase->planificacion)
                                <a href="{{ route('planificaciones.editar', $clase->planificacion) }}" wire:navigate class="text-sm font-semibold text-[#b01e28] hover:underline">Ver planificación</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-zinc-500">No hay clases programadas para hoy.</p>
                @endforelse
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-4">
            {{-- Mi progreso de collar --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Mi progreso de collar</h2>
                <div class="mt-4 flex items-center gap-3">
                    <span class="h-9 w-1.5 rounded-full bg-[#b01e28]"></span>
                    <div>
                        <p class="text-xl font-extrabold text-zinc-900 dark:text-white">{{ $collar ?: 'Sin distintivo aún' }}</p>
                        <p class="text-sm text-zinc-500">{{ $collar ? 'Distintivo actual' : 'Umbrales por confirmar' }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-zinc-500">
                    Créditos de graduación acumulados: <span class="font-semibold text-zinc-900 dark:text-white">{{ $conteoCollar }}</span>.
                    @unless ($collar) Umbral por confirmar por la federación. @endunless
                </p>
            </div>

            {{-- Por revisar --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Por revisar</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($porRevisar as $item)
                        @php($punto = ['amber' => 'bg-amber-500', 'blue' => 'bg-blue-500', 'green' => 'bg-emerald-500'][$item['color']] ?? 'bg-zinc-400')
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $punto }}"></span>
                            <span class="text-zinc-700 dark:text-zinc-200">{{ $item['texto'] }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-zinc-500">Nada pendiente por ahora. 🎉</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
