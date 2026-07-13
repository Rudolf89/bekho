<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('planillas.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a planillas
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">{{ $planilla->nombre }}</flux:heading>
        <flux:text class="mt-1">
            {{ $planilla->grupo_etario->etiqueta() }} · {{ $planilla->nivel->etiqueta() }} ·
            {{ $planilla->programa?->nombre ?? 'Regular' }}
        </flux:text>
    </div>

    <form wire:submit="guardar" class="space-y-6">
        {{-- Habilidad para la Vida --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:select wire:model="habilidad_vida" label="Habilidad para la Vida (del día/semana)" placeholder="Sin definir">
                @foreach ($habilidades as $h)
                    <flux:select.option value="{{ $h->value }}">{{ $h->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Bloques de actividad --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Bloques de actividad</flux:heading>
            <flux:text size="sm" class="mt-1">Contenido para {{ $planilla->grupo_etario->etiqueta() }}.</flux:text>

            <div class="mt-4 space-y-4">
                @foreach ($planilla->bloques as $bloque)
                    <div wire:key="bloque-{{ $bloque->id }}">
                        <flux:textarea
                            wire:model="contenidos.{{ $bloque->id }}"
                            :label="$bloque->tipo->etiqueta()"
                            rows="2"
                            placeholder="Contenido del bloque…" />
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Cuadrantes de Enseñanza --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Cuadrantes de Enseñanza</flux:heading>
            <flux:text size="sm" class="mt-1">Lista de verificación pedagógica que aplicas a toda la clase.</flux:text>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($planilla->cuadrantes as $cuadrante)
                    <div wire:key="cuad-{{ $cuadrante->id }}">
                        <flux:textarea
                            wire:model="notas.{{ $cuadrante->id }}"
                            :label="$cuadrante->cuadrante->etiqueta()"
                            :description="$cuadrante->cuadrante->descripcion()"
                            rows="2"
                            placeholder="Nota…" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="check">Guardar planilla</flux:button>
        </div>
    </form>
</div>
