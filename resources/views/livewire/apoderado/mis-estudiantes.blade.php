<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">Mis estudiantes</flux:heading>
        <flux:text class="mt-1">Información y estado de cuenta de tus hijos</flux:text>
    </div>

    @if ($matriculas->isNotEmpty())
        <x-tabla.resumen :total="$matriculas->count()" etiqueta="alumno" :plural="'alumnos'" />
    @endif

    @forelse ($matriculas as $matricula)
        @php($estado = $estados[$matricula->id] ?? ['moroso' => false, 'deuda' => 0, 'cargos' => 0])
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
                <flux:badge :color="$estado['moroso'] ? 'amber' : 'green'" size="sm">
                    {{ $estado['moroso'] ? 'Pago pendiente' : 'Al día' }}
                </flux:badge>
            </div>

            @if ($estado['cargos'] > 0)
                <div class="mt-4 flex flex-col gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                    <flux:text size="sm">
                        Deuda: <span class="font-semibold">${{ number_format($estado['deuda'], 0, ',', '.') }}</span>
                        · {{ $estado['cargos'] }} {{ $estado['cargos'] === 1 ? 'cargo pendiente' : 'cargos pendientes' }}
                    </flux:text>
                    <flux:button wire:click="abrirInformar({{ $matricula->id }})" size="sm" variant="primary" icon="arrow-up-tray">
                        Informar pago
                    </flux:button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:text>No tienes estudiantes asociados. Si crees que es un error, contacta a la escuela.</flux:text>
        </div>
    @endforelse

    {{-- Modal informar pago (comprobante) --}}
    <flux:modal name="informar-modal" wire:model="mostrarInformar" class="max-w-md md:min-w-md">
        <form wire:submit="informarPago" class="space-y-5">
            <flux:heading size="lg">Informar pago</flux:heading>
            <flux:text>Sube el comprobante de la transferencia. Quedará por verificar hasta que la escuela lo confirme.</flux:text>

            <flux:input wire:model="pagoMonto" type="number" label="Monto (CLP)" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="pagoBanco" label="Banco (opcional)" />
                <flux:input wire:model="pagoReferencia" label="Referencia (opcional)" placeholder="N° transferencia" />
            </div>
            <flux:input wire:model="comprobante" type="file" label="Comprobante (JPG, PNG o PDF)" required />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarInformar', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Enviar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
