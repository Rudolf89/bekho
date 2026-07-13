<div class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">Tomar asistencia</flux:heading>
        <flux:text class="mt-1">Pasa lista de la clase del día</flux:text>
    </div>

    {{-- Selección de clase y fecha --}}
    <div class="grid gap-3 sm:grid-cols-2">
        <flux:select wire:model.live="claseId" placeholder="Selecciona una clase">
            @foreach ($clases as $c)
                <flux:select.option value="{{ $c->id }}">
                    {{ $c->dia_semana->etiqueta() }} {{ substr((string) $c->hora_inicio, 0, 5) }} · {{ $c->nombre }} ({{ $c->sede?->nombre }})
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="fecha" type="date" />
    </div>

    @if ($clase)
        {{-- Contador --}}
        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ $clase->grupo_etario->etiqueta() }}</flux:text>
            <flux:badge color="green" size="lg">{{ $presentes }} / {{ $roster->count() }} presentes</flux:badge>
        </div>

        {{-- Roster --}}
        <div class="space-y-2">
            @forelse ($roster as $estudiante)
                @php($estado = $estados[$estudiante->id] ?? null)
                <div wire:key="ros-{{ $estudiante->id }}"
                    class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading class="min-w-0 truncate">{{ $estudiante->nombre }}</flux:heading>
                    <div class="flex shrink-0 gap-2">
                        <flux:button size="sm"
                            :variant="$estado === 'presente' ? 'primary' : 'outline'"
                            wire:click="marcar({{ $estudiante->id }}, 'presente')">
                            Presente
                        </flux:button>
                        <flux:button size="sm"
                            :variant="$estado === 'ausente' ? 'danger' : 'outline'"
                            wire:click="marcar({{ $estudiante->id }}, 'ausente')">
                            Ausente
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <flux:text>No hay estudiantes activos para esta clase (misma sede y grupo etario).</flux:text>
                </div>
            @endforelse
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>Selecciona una clase para pasar lista.</flux:text>
        </div>
    @endif
</div>
