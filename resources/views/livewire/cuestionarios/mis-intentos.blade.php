<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('cuestionarios.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a cuestionarios
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">Mis intentos</flux:heading>
        <flux:text class="mt-1">Tu historial de evaluaciones y el estado de cada una.</flux:text>
    </div>

    @if ($intentos->isEmpty())
        <flux:callout icon="clipboard-document-check">
            Todavía no has rendido ningún cuestionario.
        </flux:callout>
    @endif

    <div class="space-y-3">
        @foreach ($intentos as $intento)
            <div wire:key="int-{{ $intento->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $intento->cuestionario?->titulo ?? 'Cuestionario' }}</flux:heading>
                            <flux:badge size="sm" :color="$intento->estado->color()">{{ $intento->estado->etiqueta() }}</flux:badge>
                        </div>
                        <flux:text size="sm" class="mt-1 block text-zinc-500">
                            {{ $intento->correctas }}/{{ $intento->total }} correctas ·
                            <span class="font-semibold {{ $intento->porcentaje >= ($intento->cuestionario?->umbral_aprobacion ?? 80) ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $intento->porcentaje }}%</span>
                            @if ($intento->finalizado_at) · {{ $intento->finalizado_at->format('d-m-Y H:i') }} @endif
                        </flux:text>

                        {{-- Justificación / comentario del examinador --}}
                        @if ($intento->justificacion)
                            <div class="mt-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900/60">
                                <span class="font-semibold text-zinc-600 dark:text-zinc-300">Nota del examinador{{ $intento->revisor ? ' ('.$intento->revisor->name.')' : '' }}:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $intento->justificacion }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($intento->estado === \App\Enums\EstadoIntento::Reintentar && $intento->cuestionario)
                        <flux:button :href="route('cuestionarios.rendir', $intento->cuestionario)" icon="arrow-path" size="sm" variant="primary" wire:navigate>
                            Reintentar
                        </flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{ $intentos->links() }}
</div>
