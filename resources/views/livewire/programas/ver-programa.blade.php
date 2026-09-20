<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('programas.index')" wire:navigate>Programas</flux:button>
        <flux:heading size="xl" class="mt-2">{{ $programa->nombre }}</flux:heading>
        @if ($programa->descripcion)
            <flux:text class="mt-1">{{ $programa->descripcion }}</flux:text>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($etapas as $fila)
            @php($etapa = $fila['modelo'])
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">{{ $etapa->nombre }}</flux:heading>
                    <div class="flex items-center gap-3 text-sm text-zinc-500">
                        @if ($etapa->horas_requeridas)
                            <span>{{ $etapa->horas_requeridas }} h</span>
                        @endif
                        <span>{{ $fila['avance']['completados'] }}/{{ $fila['avance']['total'] }} contenidos</span>
                    </div>
                </div>

                @if ($etapa->descripcion)
                    <flux:text class="mt-1">{{ $etapa->descripcion }}</flux:text>
                @endif

                @php($contenidos = $etapa->contenidos()->where('activo', true)->get())
                @if ($contenidos->isNotEmpty())
                    <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($contenidos as $contenido)
                            <li>
                                <a href="{{ route('programas.contenido', $contenido) }}" wire:navigate
                                   class="flex items-center justify-between py-2 text-sm hover:text-emerald-600">
                                    <span>{{ $contenido->titulo }}</span>
                                    <flux:badge size="sm" variant="subtle">{{ $contenido->tipo->etiqueta() }}</flux:badge>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($etapa->requisitos->isNotEmpty())
                    <div class="mt-4">
                        <flux:text class="text-xs font-semibold uppercase text-zinc-500">Requisitos de avance</flux:text>
                        <ul class="mt-1 list-inside list-disc text-sm text-zinc-600 dark:text-zinc-300">
                            @foreach ($etapa->requisitos as $req)
                                <li>{{ $req->descripcion }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @empty
            <flux:text>Este programa aún no tiene etapas.</flux:text>
        @endforelse
    </div>
</div>
