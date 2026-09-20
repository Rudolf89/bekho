<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <flux:button variant="ghost" size="sm" icon="arrow-left"
                     :href="route('programas.programa', $contenido->etapaPrograma->programa_id)" wire:navigate>
            Volver al programa
        </flux:button>
        <flux:badge size="sm">{{ $indice + 1 }} / {{ $total }}</flux:badge>
    </div>

    <div>
        <flux:heading size="xl">{{ $contenido->titulo }}</flux:heading>
        @if ($contenido->descripcion)
            <flux:text class="mt-1">{{ $contenido->descripcion }}</flux:text>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        @if ($contenido->tipo === \App\Enums\TipoContenido::Texto)
            <div class="prose max-w-none whitespace-pre-line dark:prose-invert">{{ $contenido->cuerpo }}</div>
        @elseif ($contenido->tipo === \App\Enums\TipoContenido::Video && $urlIncrustada)
            <div class="aspect-video w-full overflow-hidden rounded-lg">
                <iframe src="{{ $urlIncrustada }}" class="h-full w-full" frameborder="0" allowfullscreen></iframe>
            </div>
        @else
            <flux:button icon="arrow-top-right-on-square" :href="$contenido->url_recurso" target="_blank">
                Abrir recurso
            </flux:button>
        @endif
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2">
            @if ($anterior)
                <flux:button variant="ghost" size="sm" icon="chevron-left" :href="route('programas.contenido', $anterior)" wire:navigate>Anterior</flux:button>
            @endif
            @if ($siguiente)
                <flux:button variant="ghost" size="sm" icon:trailing="chevron-right" :href="route('programas.contenido', $siguiente)" wire:navigate>Siguiente</flux:button>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <flux:badge size="sm" :color="$estado === \App\Enums\EstadoProgreso::Completado ? 'green' : 'zinc'">
                {{ $estado->etiqueta() }}
            </flux:badge>
            <flux:button variant="primary" size="sm" wire:click="completarYSeguir">
                {{ $siguiente ? 'Completar y continuar →' : 'Completar y terminar' }}
            </flux:button>
        </div>
    </div>
</div>
