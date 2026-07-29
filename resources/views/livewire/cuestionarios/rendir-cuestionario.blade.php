<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('cuestionarios.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a cuestionarios
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">{{ $cuestionario->titulo }}</flux:heading>
        @if ($cuestionario->descripcion)
            <flux:text class="mt-1">{{ $cuestionario->descripcion }}</flux:text>
        @endif
        <flux:text size="sm" class="mt-1 block text-zinc-500">
            {{ $cuestionario->preguntas->count() }} preguntas · aprueba con {{ $cuestionario->umbral_aprobacion }}%
        </flux:text>
    </div>

    {{-- Resultado --}}
    @if ($finalizado)
        <div class="rounded-xl border p-5 {{ $aprobado ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950/40' : 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40' }}">
            <flux:heading size="lg">{{ $aprobado ? '¡Aprobado!' : 'No alcanzó el mínimo' }}</flux:heading>
            <flux:text class="mt-1">
                Obtuviste <span class="font-bold">{{ $correctas }}/{{ $total }}</span> correctas
                (<span class="font-bold">{{ $porcentaje }}%</span>). Mínimo para aprobar: {{ $cuestionario->umbral_aprobacion }}%.
            </flux:text>
            <div class="mt-3 flex gap-2">
                <flux:button wire:click="reintentar" icon="arrow-path" size="sm" variant="primary">Reintentar</flux:button>
                <flux:button :href="route('cuestionarios.index')" size="sm" variant="subtle" wire:navigate>Salir</flux:button>
            </div>
        </div>
    @endif

    {{-- Preguntas --}}
    <div class="space-y-4">
        @foreach ($cuestionario->preguntas as $indice => $pregunta)
            @php($multiple = $pregunta->esMultiple())
            @php($acierto = $finalizado ? $this->aciertoEn($pregunta) : null)
            <div wire:key="p-{{ $pregunta->id }}"
                 class="rounded-xl border bg-white p-4 dark:bg-zinc-800 {{ $finalizado ? ($acierto ? 'border-green-300 dark:border-green-800' : 'border-red-300 dark:border-red-800') : 'border-zinc-200 dark:border-zinc-700' }}">
                <div class="flex items-start gap-2">
                    <span class="shrink-0 font-bold text-zinc-400">{{ $indice + 1 }}.</span>
                    <div class="min-w-0 flex-1">
                        <flux:text class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $pregunta->enunciado }}</flux:text>
                        @if ($multiple && ! $finalizado)
                            <flux:text size="xs" class="mt-0.5 block text-zinc-400">Selecciona todas las que correspondan.</flux:text>
                        @endif

                        <div class="mt-3 space-y-2">
                            @foreach ($pregunta->opciones as $opcion)
                                @php($seleccionada = $this->elegida($pregunta->id, $opcion->id))
                                <button
                                    type="button"
                                    @disabled($finalizado)
                                    wire:click="alternar({{ $pregunta->id }}, {{ $opcion->id }}, {{ $multiple ? 'true' : 'false' }})"
                                    class="flex w-full items-start gap-3 rounded-lg border px-3 py-2 text-left text-sm transition-colors
                                        @if ($finalizado)
                                            @if ($opcion->correcta) border-green-400 bg-green-50 dark:border-green-700 dark:bg-green-950/40
                                            @elseif ($seleccionada) border-red-400 bg-red-50 dark:border-red-700 dark:bg-red-950/40
                                            @else border-zinc-200 dark:border-zinc-700 @endif
                                        @else
                                            {{ $seleccionada ? 'border-sky-500 bg-sky-50 dark:border-sky-500 dark:bg-sky-950/40' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}
                                        @endif"
                                >
                                    <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-{{ $multiple ? 'md' : 'full' }} border-2
                                        {{ $seleccionada ? 'border-sky-500 bg-sky-500 text-white' : 'border-zinc-300 dark:border-zinc-600' }}">
                                        @if ($seleccionada)<span class="text-xs">✓</span>@endif
                                    </span>
                                    <span class="text-zinc-800 dark:text-zinc-100">{{ $opcion->texto }}</span>
                                    @if ($finalizado && $opcion->correcta)
                                        <span class="ml-auto shrink-0 text-xs font-semibold text-green-600 dark:text-green-400">correcta</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        {{-- Explicación tras finalizar --}}
                        @if ($finalizado && $pregunta->explicacion)
                            <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900/60">
                                <span class="font-semibold text-zinc-600 dark:text-zinc-300">Por qué:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $pregunta->explicacion }}</span>
                                @if ($pregunta->nota)
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">⚠ {{ $pregunta->nota }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @unless ($finalizado)
        <div class="flex justify-end">
            <flux:button wire:click="enviar" variant="primary" icon="check">Enviar y corregir</flux:button>
        </div>
    @endunless
</div>
