<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-2">
        <flux:button :href="route('formacion.nivel', $contenido->nivel_id)" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver al nivel
        </flux:button>
        @if ($siguiente || $anterior)
            <flux:text size="sm" class="text-zinc-400">
                {{ $indice + 1 }} de {{ $total }}
            </flux:text>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-start justify-between gap-4">
            <flux:heading size="xl">{{ $contenido->titulo }}</flux:heading>
            <flux:badge
                color="{{ $estado === \App\Enums\EstadoProgreso::Completado ? 'green' : ($estado === \App\Enums\EstadoProgreso::Visto ? 'amber' : 'zinc') }}"
                size="sm">
                {{ $estado->etiqueta() }}
            </flux:badge>
        </div>

        @if ($contenido->descripcion)
            <flux:text class="mt-2">{{ $contenido->descripcion }}</flux:text>
        @endif

        <flux:separator class="my-6" />

        {{-- Cuerpo según el tipo de contenido --}}
        @if ($contenido->tipo === \App\Enums\TipoContenido::Texto)
            <div class="prose prose-zinc max-w-none whitespace-pre-line dark:prose-invert">
                {{ $contenido->cuerpo }}
            </div>
        @elseif ($contenido->tipo === \App\Enums\TipoContenido::Video)
            @if ($urlIncrustada)
                <div class="relative w-full overflow-hidden rounded-lg" style="aspect-ratio: 16 / 9;">
                    <iframe src="{{ $urlIncrustada }}" class="absolute inset-0 h-full w-full" title="{{ $contenido->titulo }}"
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen loading="lazy"></iframe>
                </div>
            @else
                <flux:callout icon="film">
                    <flux:callout.heading>Video externo</flux:callout.heading>
                    <flux:callout.text>
                        <flux:link href="{{ $contenido->url_recurso }}" target="_blank" rel="noopener">
                            Abrir el video en una pestaña nueva
                        </flux:link>
                    </flux:callout.text>
                </flux:callout>
            @endif
        @elseif ($contenido->tipo === \App\Enums\TipoContenido::Documento)
            <flux:callout icon="document-text">
                <flux:callout.heading>Documento</flux:callout.heading>
                <flux:callout.text>
                    <flux:link href="{{ $contenido->url_recurso }}" target="_blank" rel="noopener">
                        Abrir el documento en una pestaña nueva
                    </flux:link>
                </flux:callout.text>
            </flux:callout>
        @endif
    </div>

    {{-- Navegación entre capítulos del nivel --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        {{-- Anterior --}}
        <div>
            @if ($anterior)
                <flux:button :href="route('formacion.contenido', $anterior)" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
                    Anterior
                </flux:button>
            @endif
        </div>

        {{-- Acción principal --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($estado === \App\Enums\EstadoProgreso::Completado)
                <flux:badge color="green" size="sm" icon="check-circle">Completado</flux:badge>
                @if ($siguiente)
                    <flux:button :href="route('formacion.contenido', $siguiente)" icon:trailing="arrow-right" variant="primary" wire:navigate>
                        Siguiente
                    </flux:button>
                @else
                    <flux:button :href="route('formacion.nivel', $contenido->nivel_id)" variant="primary" wire:navigate>
                        Terminar nivel
                    </flux:button>
                @endif
            @elseif ($siguiente)
                <flux:button wire:click="completar" icon="check" variant="filled">
                    Marcar como completado
                </flux:button>
                <flux:button wire:click="completarYSeguir" icon:trailing="arrow-right" variant="primary">
                    Completar y continuar
                </flux:button>
            @else
                <flux:button wire:click="completarYSeguir" icon="check" variant="primary">
                    Completar y terminar nivel
                </flux:button>
            @endif
        </div>
    </div>
</div>
