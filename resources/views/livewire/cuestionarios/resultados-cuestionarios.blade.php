<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button :href="route('cuestionarios.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a cuestionarios
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">Resultados de cuestionarios</flux:heading>
        <flux:text class="mt-1">
            Intentos de los alumnos y decisión de aprobación.
            @if ($pendientes > 0)
                <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $pendientes }} en revisión.</span>
            @endif
        </flux:text>
    </div>

    {{-- Filtros --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="buscar" placeholder="Buscar por alumno o cuestionario" icon="magnifying-glass" label="Buscar" />
        <flux:select wire:model.live="cuestionarioId" label="Cuestionario" placeholder="Todos">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($cuestionarios as $c)
                <flux:select.option value="{{ $c->id }}">{{ $c->titulo }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="estado" label="Estado" placeholder="Todos">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($estados as $e)
                <flux:select.option value="{{ $e->value }}">{{ $e->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($intentos->isEmpty())
        <flux:callout icon="clipboard-document-check">No hay intentos con esos filtros.</flux:callout>
    @endif

    <div class="space-y-3">
        @foreach ($intentos as $intento)
            @php($umbral = $intento->cuestionario?->umbral_aprobacion ?? 80)
            <div wire:key="res-{{ $intento->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg">{{ $intento->user?->name ?? 'Alumno' }}</flux:heading>
                            <flux:badge size="sm" :color="$intento->estado->color()">{{ $intento->estado->etiqueta() }}</flux:badge>
                        </div>
                        <flux:text size="sm" class="mt-1 block text-zinc-500">
                            {{ $intento->cuestionario?->titulo }} ·
                            {{ $intento->correctas }}/{{ $intento->total }} ·
                            <span class="font-semibold {{ $intento->porcentaje >= $umbral ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $intento->porcentaje }}%</span>
                            (mín. {{ $umbral }}%)
                            @if ($intento->finalizado_at) · {{ $intento->finalizado_at->format('d-m-Y H:i') }} @endif
                        </flux:text>
                        @if ($intento->justificacion)
                            <flux:text size="sm" class="mt-1 block text-zinc-500">
                                <span class="font-semibold">Justificación:</span> {{ $intento->justificacion }}
                                @if ($intento->revisor) — {{ $intento->revisor->name }} @endif
                            </flux:text>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($revisandoId !== $intento->id)
                            <flux:button wire:click="abrirRevision({{ $intento->id }})" icon="pencil-square" size="sm" variant="subtle">
                                {{ $intento->estado === \App\Enums\EstadoIntento::Pendiente ? 'Revisar' : 'Cambiar' }}
                            </flux:button>
                        @endif
                    </div>
                </div>

                {{-- Panel de decisión --}}
                @if ($revisandoId === $intento->id)
                    <div class="mt-4 space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/60">
                        @if ($intento->requiereJustificacion())
                            <flux:callout size="sm" icon="exclamation-triangle" color="amber">
                                Este intento no alcanzó el mínimo ({{ $intento->porcentaje }}% &lt; {{ $umbral }}%).
                                Aprobarlo es una excepción y requiere justificación.
                            </flux:callout>
                        @endif

                        <flux:textarea
                            wire:model="justificacion"
                            label="Justificación / comentario"
                            rows="2"
                            placeholder="Motivo de la decisión (obligatorio para aprobar por excepción)…" />
                        @error('justificacion') <flux:text size="xs" class="text-red-600">{{ $message }}</flux:text> @enderror

                        <div class="flex flex-wrap gap-2">
                            <flux:button wire:click="aprobar({{ $intento->id }})" icon="check" size="sm" variant="primary">
                                Aprobar
                            </flux:button>
                            <flux:button wire:click="reintentar({{ $intento->id }})" icon="arrow-path" size="sm" variant="danger">
                                Volver a intentar
                            </flux:button>
                            <flux:button wire:click="cerrarRevision" size="sm" variant="ghost">Cancelar</flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($intentos->total() > 0)
        <x-tabla.resumen :total="$intentos->total()" etiqueta="intento" class="rounded-xl border border-zinc-200 dark:border-zinc-700" />
    @endif

    {{ $intentos->links() }}
</div>
