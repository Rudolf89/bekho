<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">Mis estudiantes</flux:heading>
        <flux:text class="mt-1">Información de tus hijos en la escuela</flux:text>
    </div>

    @if ($hijos->isNotEmpty())
        <x-tabla.resumen :total="$hijos->count()" etiqueta="estudiante" />
    @endif

    @forelse ($hijos as $hijo)
        <div wire:key="hijo-{{ $hijo->id }}"
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $hijo->nombre }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $hijo->grupo_etario->etiqueta() }} · {{ $hijo->nivel->etiqueta() }}
                        @if ($hijo->grado) · {{ $hijo->grado->nombre }} @endif
                    </flux:text>
                    @if ($hijo->sede)
                        <flux:text size="sm" class="mt-1">Sede: {{ $hijo->sede->nombre }}</flux:text>
                    @endif
                </div>
                <flux:badge :color="($estados[$hijo->id] ?? '') === 'moroso' ? 'amber' : 'green'" size="sm">
                    {{ ($estados[$hijo->id] ?? '') === 'moroso' ? 'Pago pendiente' : 'Al día' }}
                </flux:badge>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>No tienes estudiantes asociados. Si crees que es un error, contacta a la escuela.</flux:text>
        </div>
    @endforelse
</div>
