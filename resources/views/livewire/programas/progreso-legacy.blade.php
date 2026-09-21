<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Gestión · Progreso</flux:text>
        <flux:heading size="xl" class="mt-1">Programa Legacy</flux:heading>
    </div>

    @if (! $programa)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>El programa Legacy todavía no está en el catálogo de la federación.</flux:text>
        </div>
    @elseif (! $inscripcion)
        {{-- Sin inscripción no hay avance que mostrar: la crea quien gestiona inscripciones. --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">No estás inscrito en el Programa Legacy</flux:heading>
            <flux:text class="mt-1">
                El track de formación de instructores se lleva por persona. Tu instructor o la dirección
                te inscriben desde Inscripciones, y desde ahí registran tus horas y requisitos.
            </flux:text>

            @can('gestionar inscripciones')
                <flux:button class="mt-4" size="sm" icon="academic-cap" :href="route('programas.gestion')" wire:navigate>
                    Ir a Inscripciones
                </flux:button>
            @endcan
        </div>
    @else
        @php($etapaActual = $inscripcion->etapaActual)

        {{-- Cabecera: etapa en curso y su avance. --}}
        <div class="rounded-xl bg-zinc-800 p-6 text-white dark:bg-zinc-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">
                        Programa {{ $programa->nombre }} · Formación de instructores
                    </flux:text>

                    <flux:heading size="xl" class="mt-1 text-white">
                        {{ $etapaActual?->nombre ?? 'Programa completado' }}
                    </flux:heading>
                </div>

                @if ($cuestionarioPendiente)
                    @can('rendir cuestionarios')
                        <flux:button variant="danger" icon="pencil-square"
                                     :href="route('cuestionarios.rendir', $cuestionarioPendiente)" wire:navigate>
                            Rendir examen escrito
                        </flux:button>
                    @endcan
                @endif
            </div>

            @if ($resumen)
                <div class="mt-5 h-3 w-full overflow-hidden rounded-full bg-zinc-600">
                    <div class="h-full rounded-full bg-[#b01e28]" style="width: {{ $resumen['porcentaje'] }}%"></div>
                </div>
                <flux:text size="sm" class="mt-2 text-zinc-300">
                    {{ $resumen['requisitosCumplidos'] }} de {{ $resumen['requisitosTotal'] }} requisitos ·
                    {{ (int) $resumen['horas'] }} de {{ $resumen['horasRequeridas'] }} h de asistencia
                </flux:text>
            @else
                <flux:text size="sm" class="mt-3 text-zinc-300">
                    Inscripción aprobada
                    @if ($inscripcion->fecha_aprobacion)
                        el {{ $inscripcion->fecha_aprobacion->format('d-m-Y') }}
                    @endif.
                </flux:text>
            @endif
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
            {{-- Niveles del programa --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">Niveles del programa</flux:heading>
                </div>

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach ($niveles as $nivel)
                        @php($etapa = $nivel['etapa'])
                        <li class="flex items-start gap-4 px-5 py-4">
                            <div @class([
                                'flex h-10 w-12 shrink-0 items-center justify-center rounded text-xs font-semibold',
                                'bg-zinc-800 text-white dark:bg-zinc-700' => $nivel['estado'] !== 'bloqueada',
                                'bg-zinc-100 text-zinc-400 dark:bg-zinc-700/50 dark:text-zinc-500' => $nivel['estado'] === 'bloqueada',
                            ])>
                                {{ $nivel['porcentaje'] }}%
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:heading>{{ $etapa->nombre }}</flux:heading>

                                    @if ($nivel['estado'] === 'aprobada')
                                        <flux:badge size="sm" color="green">Completado</flux:badge>
                                    @elseif ($nivel['estado'] === 'en_curso')
                                        <flux:badge size="sm" color="sky">En curso</flux:badge>
                                    @else
                                        <flux:badge size="sm" variant="subtle">Bloqueado</flux:badge>
                                    @endif
                                </div>

                                <flux:text size="sm" class="mt-0.5">
                                    {{ $etapa->horas_requeridas }} h de asistencia
                                    @if ($etapa->edad_minima)
                                        · desde {{ $etapa->edad_minima }} años
                                    @endif
                                    @if ($etapa->gradoMinimo)
                                        · {{ $etapa->gradoMinimo->nombre }}
                                    @endif
                                    · {{ $etapa->requisitos->count() }} requisitos
                                </flux:text>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Pendiente para certificar la etapa en curso --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <flux:heading size="lg">Pendiente para certificar</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        Lo registra tu instructor; aquí es solo consulta.
                    </flux:text>
                </div>

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($pendientes as $item)
                        <li class="flex items-start gap-3 px-5 py-3">
                            <span class="mt-0.5 size-5 shrink-0 rounded border border-zinc-300 dark:border-zinc-600"></span>

                            <div class="min-w-0">
                                <flux:text>{{ $item['texto'] }}</flux:text>

                                @if ($item['requisito']?->esAutomatico())
                                    @can('rendir cuestionarios')
                                        <flux:link class="text-sm" :href="route('cuestionarios.rendir', $item['requisito']->cuestionario)" wire:navigate>
                                            Rendir ahora →
                                        </flux:link>
                                    @endcan
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="flex items-center gap-3 px-5 py-4">
                            <flux:icon.check-circle variant="solid" class="size-5 shrink-0 text-emerald-600" />
                            <flux:text>No queda nada pendiente: puedes pedir el ascenso a tu instructor.</flux:text>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
