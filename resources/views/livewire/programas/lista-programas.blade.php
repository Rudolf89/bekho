<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Programas</flux:heading>
            <flux:text class="mt-1">Rutas formativas: manuales de estudio y programas de instructores.</flux:text>
        </div>
        <x-tabla.buscador />
    </div>

    <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
        @forelse ($programas as $fila)
            @php($p = $fila['modelo'])
            <a href="{{ route('programas.programa', $p) }}" wire:navigate
               class="block rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg" class="truncate">{{ $p->nombre }}</flux:heading>
                    <flux:badge size="sm">{{ $p->tipo->etiqueta() }}</flux:badge>
                </div>
                @if ($p->descripcion)
                    <flux:text class="mt-1 line-clamp-2">{{ $p->descripcion }}</flux:text>
                @endif
                <div class="mt-3 flex items-center justify-between text-sm text-zinc-500">
                    <span>{{ $p->etapas->count() }} {{ \Illuminate\Support\Str::plural('etapa', $p->etapas->count()) }}</span>
                    <span>{{ $fila['porcentaje'] }}% completado</span>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $fila['porcentaje'] }}%"></div>
                </div>
            </a>
        @empty
            <flux:text class="col-span-full">No hay programas disponibles.</flux:text>
        @endforelse
    </div>
</div>
