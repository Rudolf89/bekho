<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Formación</flux:heading>
            <flux:text class="mt-1">Plataforma de formación de instructores</flux:text>
        </div>

        @can('gestionar formacion')
            <flux:button :href="route('formacion.admin.niveles')" icon="cog-6-tooth" variant="ghost" size="sm" wire:navigate>
                Administrar
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabla.buscador placeholder="Buscar nivel…" />
        @if ($niveles->isNotEmpty())
            <x-tabla.resumen :total="$niveles->count()" etiqueta="nivel" plural="niveles" class="w-full sm:w-auto" />
        @endif
    </div>

    @forelse ($niveles as $item)
        @php($nivel = $item['modelo'])
        @php($avance = $item['avance'])

        <a href="{{ route('formacion.nivel', $nivel) }}" wire:navigate
            class="block rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ $nivel->nombre }}</flux:heading>
                    @if ($nivel->descripcion)
                        <flux:text class="mt-1 line-clamp-2">{{ $nivel->descripcion }}</flux:text>
                    @endif
                </div>
                <flux:badge color="{{ $avance['porcentaje'] === 100 ? 'green' : 'zinc' }}" size="sm">
                    {{ $avance['completados'] }}/{{ $avance['total'] }}
                </flux:badge>
            </div>

            <div class="mt-4">
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $avance['porcentaje'] }}%"></div>
                </div>
                <flux:text size="sm" class="mt-1">{{ $avance['porcentaje'] }}% completado</flux:text>
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>{{ $buscar !== '' ? 'Sin resultados para tu búsqueda.' : 'Todavía no hay niveles de formación disponibles.' }}</flux:text>
        </div>
    @endforelse
</div>
