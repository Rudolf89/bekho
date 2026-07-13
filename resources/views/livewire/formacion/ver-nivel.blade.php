<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('formacion.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a niveles
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="xl">{{ $nivel->nombre }}</flux:heading>
        @if ($nivel->descripcion)
            <flux:text class="mt-2">{{ $nivel->descripcion }}</flux:text>
        @endif

        <div class="mt-4">
            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $avance['porcentaje'] }}%"></div>
            </div>
            <flux:text size="sm" class="mt-1">
                {{ $avance['completados'] }} de {{ $avance['total'] }} completados ({{ $avance['porcentaje'] }}%)
            </flux:text>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($contenidos as $item)
            @php($contenido = $item['modelo'])
            @php($estado = $item['estado'])

            <a href="{{ route('formacion.contenido', $contenido) }}" wire:navigate
                class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
                <div class="flex min-w-0 items-center gap-3">
                    <flux:icon.play-circle
                        @class([
                            'size-6 shrink-0',
                            'text-emerald-500' => $estado === \App\Enums\EstadoProgreso::Completado,
                            'text-zinc-400' => $estado !== \App\Enums\EstadoProgreso::Completado,
                        ]) />
                    <div class="min-w-0">
                        <flux:heading>{{ $contenido->titulo }}</flux:heading>
                        <flux:text size="sm">{{ $contenido->tipo->etiqueta() }}</flux:text>
                    </div>
                </div>

                <flux:badge
                    color="{{ $estado === \App\Enums\EstadoProgreso::Completado ? 'green' : ($estado === \App\Enums\EstadoProgreso::Visto ? 'amber' : 'zinc') }}"
                    size="sm">
                    {{ $estado->etiqueta() }}
                </flux:badge>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <flux:text>Este nivel todavía no tiene contenidos.</flux:text>
            </div>
        @endforelse
    </div>
</div>
