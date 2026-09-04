<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Programa Legacy</flux:heading>
        <flux:text class="mt-1">Track de formación de instructores: horas, requisitos y ascenso de nivel.</flux:text>
    </div>

    {{-- Nueva inscripción --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-3">Inscribir en un nivel</flux:heading>
        {{-- El error inline de Flux estira el campo y desalinea la fila; se oculta
             y el mensaje se muestra estable debajo (@error). --}}
        <div class="flex flex-wrap items-end gap-3 [&_[data-flux-error]]:hidden">
            <flux:select wire:model="nuevoUserId" label="Usuario" placeholder="Elige…" class="min-w-48 flex-1">
                <flux:select.option value="">Elige…</flux:select.option>
                @foreach ($usuarios as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="nuevoNivelId" label="Nivel" placeholder="Elige…" class="min-w-48 flex-1">
                <flux:select.option value="">Elige…</flux:select.option>
                @foreach ($niveles as $n)
                    <flux:select.option value="{{ $n->id }}">{{ $n->nombre }} ({{ $n->horas_requeridas }} h)</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button wire:click="crearInscripcion" variant="primary" icon="plus">Inscribir</flux:button>
        </div>
        @error('nuevoUserId') <flux:text size="xs" class="mt-1 text-red-600">{{ $message }}</flux:text> @enderror
        @error('nuevoNivelId') <flux:text size="xs" class="mt-1 text-red-600">{{ $message }}</flux:text> @enderror
    </div>

    {{-- Inscripciones --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar por instructor o nivel…" />
        @if ($inscripciones->isNotEmpty())
            <x-tabla.resumen
                :total="$inscripciones->count()"
                etiqueta="inscripción"
                plural="inscripciones"
                :sumas="[['etiqueta' => 'Horas acumuladas', 'valor' => number_format($horasTotales, 0, ',', '.')]]"
                class="w-full sm:w-auto"
            />
        @endif
    </div>

    <div class="space-y-2">
        @forelse ($inscripciones as $ins)
            @php($acumuladas = $ins->horasAcumuladas())
            @php($req = $ins->nivel->horas_requeridas)
            @php($pct = $req > 0 ? min(100, (int) round($acumuladas / $req * 100)) : 0)
            <button type="button" wire:key="ins-{{ $ins->id }}" wire:click="$set('inscripcionId', {{ $ins->id }})"
                    class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition-colors {{ $inscripcion?->id === $ins->id ? 'border-red-400 bg-red-50 dark:border-red-700 dark:bg-red-950/30' : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800' }}">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <flux:text class="font-semibold">{{ $ins->user?->name }}</flux:text>
                        <flux:badge size="sm" :color="$ins->estado->color()">{{ $ins->estado->etiqueta() }}</flux:badge>
                    </div>
                    <flux:text size="sm" class="text-zinc-500">{{ $ins->nivel->nombre }} · {{ number_format($acumuladas, 0) }}/{{ $req }} h</flux:text>
                </div>
                <div class="w-28 shrink-0">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full rounded-full bg-green-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </button>
        @empty
            <flux:text class="py-4 text-center text-zinc-500">
                {{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Aún no hay inscripciones en el programa.' }}
            </flux:text>
        @endforelse
    </div>

    {{-- Detalle --}}
    @if ($inscripcion)
        @php($acumuladas = $inscripcion->horasAcumuladas())
        @php($req = $inscripcion->nivel->horas_requeridas)
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ $inscripcion->user?->name }} — {{ $inscripcion->nivel->nombre }}</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-zinc-500">
                        {{ number_format($acumuladas, 1) }} de {{ $req }} horas ·
                        {{ $inscripcion->cumpleTodosLosRequisitos() ? 'requisitos completos' : 'requisitos pendientes' }}
                    </flux:text>
                </div>
                @if ($inscripcion->estado === \App\Enums\EstadoLegacy::Aprobado)
                    <flux:badge size="lg" color="green">Aprobado {{ $inscripcion->fecha_aprobacion?->format('d-m-Y') }}</flux:badge>
                @elseif ($puedeAprobar)
                    <flux:button wire:click="aprobar" variant="primary" icon="check" :disabled="! $inscripcion->puedeAprobar()">
                        Aprobar ascenso
                    </flux:button>
                @endif
            </div>

            {{-- Requisitos --}}
            <div>
                <flux:heading size="sm" class="mb-2">Requisitos</flux:heading>
                <ul class="space-y-2">
                    @foreach ($inscripcion->nivel->requisitos as $requisito)
                        @php($cumplido = $inscripcion->cumpleRequisito($requisito))
                        <li wire:key="req-{{ $requisito->id }}" class="flex items-center gap-3 text-sm">
                            @if ($requisito->esAutomatico())
                                <span class="flex size-5 items-center justify-center rounded-full {{ $cumplido ? 'bg-green-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                                    @if ($cumplido) ✓ @endif
                                </span>
                                <span class="{{ $cumplido ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-500' }}">
                                    {{ $requisito->texto }}
                                    <flux:badge size="sm" color="sky" class="ml-1">auto: {{ $requisito->cuestionario?->titulo }}</flux:badge>
                                </span>
                            @else
                                <button type="button" wire:click="alternarRequisito({{ $requisito->id }})"
                                        class="flex size-5 shrink-0 items-center justify-center rounded-full border-2 {{ $cumplido ? 'border-green-500 bg-green-500 text-white' : 'border-zinc-300 dark:border-zinc-600' }}">
                                    @if ($cumplido) ✓ @endif
                                </button>
                                <span class="{{ $cumplido ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-300' }}">{{ $requisito->texto }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Horas --}}
            <div>
                <flux:heading size="sm" class="mb-2">Registro de horas</flux:heading>
                @if ($inscripcion->estado !== \App\Enums\EstadoLegacy::Aprobado)
                    <div class="mb-3 flex flex-wrap items-end gap-2 [&_[data-flux-error]]:hidden">
                        <flux:input type="date" wire:model="horaFecha" label="Fecha" class="w-40" />
                        <flux:input type="number" step="0.5" wire:model="horaCantidad" label="Horas" class="w-24" />
                        <flux:input wire:model="horaDescripcion" label="Descripción" placeholder="Opcional" class="min-w-40 flex-1" />
                        <flux:button wire:click="agregarHora" icon="plus" size="sm">Agregar</flux:button>
                    </div>
                    @error('horaFecha') <flux:text size="xs" class="text-red-600">{{ $message }}</flux:text> @enderror
                    @error('horaCantidad') <flux:text size="xs" class="text-red-600">{{ $message }}</flux:text> @enderror
                @endif

                @if ($inscripcion->horas->isEmpty())
                    <flux:text size="sm" class="text-zinc-500">Sin horas registradas.</flux:text>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                        @foreach ($inscripcion->horas as $hora)
                            <li wire:key="hora-{{ $hora->id }}" class="flex items-center justify-between gap-2 py-2 text-sm">
                                <span>
                                    <span class="font-semibold">{{ number_format($hora->horas, 1) }} h</span>
                                    <span class="text-zinc-400">· {{ $hora->fecha->format('d-m-Y') }}</span>
                                    @if ($hora->descripcion) <span class="text-zinc-500">— {{ $hora->descripcion }}</span> @endif
                                </span>
                                @if ($inscripcion->estado !== \App\Enums\EstadoLegacy::Aprobado)
                                    <flux:button wire:click="eliminarHora({{ $hora->id }})" icon="trash" size="xs" variant="subtle" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</div>
