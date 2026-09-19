<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">Mis estudiantes</flux:heading>
        <flux:text class="mt-1">Información de tus hijos en la escuela</flux:text>
    </div>

    @if ($matriculas->isNotEmpty())
        <x-tabla.resumen :total="$matriculas->count()" etiqueta="alumno" :plural="'alumnos'" />
    @endif

    @forelse ($matriculas as $matricula)
        <div wire:key="mat-{{ $matricula->id }}"
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $matricula->persona?->nombreCompleto() }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $matricula->grupo_etario->etiqueta() }}@if ($matricula->nivel) · {{ $matricula->nivel->etiqueta() }}@endif
                        @if ($matricula->persona?->grado) · {{ $matricula->persona->grado->nombre }} @endif
                    </flux:text>
                    @if ($matricula->sede)
                        <flux:text size="sm" class="mt-1">Sede: {{ $matricula->sede->nombre }}</flux:text>
                    @endif
                </div>
                <flux:badge :color="($estados[$matricula->id] ?? '') === 'moroso' ? 'amber' : 'green'" size="sm">
                    {{ ($estados[$matricula->id] ?? '') === 'moroso' ? 'Pago pendiente' : 'Al día' }}
                </flux:badge>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>No tienes estudiantes asociados. Si crees que es un error, contacta a la escuela.</flux:text>
        </div>
    @endforelse
</div>
