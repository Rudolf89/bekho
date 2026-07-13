<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Pagos</flux:heading>
            <flux:text class="mt-1">Registro de pagos y estado de morosidad</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button wire:click="$set('mostrarConfig', true)" icon="cog-6-tooth" variant="ghost">Configuración</flux:button>
            <flux:button wire:click="abrirRegistro" icon="plus" variant="primary">Registrar pago</flux:button>
        </div>
    </div>

    {{-- Morosos --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="lg">Morosos</flux:heading>
            <flux:badge color="amber" size="sm">{{ $periodo }} · {{ $morosos->count() }}</flux:badge>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Estudiante</flux:table.column>
                <flux:table.column>Grupo</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($morosos as $moroso)
                    <flux:table.row wire:key="moroso-{{ $moroso->id }}">
                        <flux:table.cell variant="strong">{{ $moroso->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $moroso->grupo_etario->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button wire:click="abrirRegistro({{ $moroso->id }})" size="sm" variant="ghost" icon="banknotes">
                                    Registrar
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">
                            <flux:text class="py-4 text-center">Nadie moroso este período. 🎉</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Pagos recientes --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="lg">Pagos recientes</flux:heading>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Estudiante</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Monto</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($pagosRecientes as $pago)
                    <flux:table.row wire:key="pago-{{ $pago->id }}">
                        <flux:table.cell>{{ $pago->fecha_pago->format('d-m-Y') }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $pago->estudiante?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $pago->tipo->etiqueta() }}</flux:table.cell>
                        <flux:table.cell>${{ number_format($pago->monto, 0, ',', '.') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <flux:text class="py-4 text-center">Aún no hay pagos registrados.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Modal registrar pago --}}
    <flux:modal name="pago-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="registrarPago" class="space-y-5">
            <flux:heading size="lg">Registrar pago</flux:heading>

            <flux:select wire:model="pagoEstudianteId" label="Estudiante" placeholder="Selecciona">
                @foreach ($estudiantes as $est)
                    <flux:select.option value="{{ $est->id }}">{{ $est->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="pagoTipo" label="Tipo">
                    @foreach ($tipos as $t)
                        <flux:select.option value="{{ $t->value }}">{{ $t->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="pagoMonto" type="number" label="Monto (CLP)" required />
                <flux:input wire:model="pagoFechaPago" type="date" label="Fecha de pago" required />
                <flux:input wire:model="pagoMedio" label="Medio (opcional)" placeholder="Efectivo, transferencia…" />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Registrar</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal configuración --}}
    <flux:modal name="config-modal" wire:model="mostrarConfig" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardarConfig" class="space-y-5">
            <flux:heading size="lg">Configuración de pagos</flux:heading>
            <flux:text>Valores de esta academia. Déjalos en blanco si aún no los defines.</flux:text>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="valor_mensualidad" type="number" label="Mensualidad (CLP)" />
                <flux:input wire:model="valor_matricula" type="number" label="Matrícula (CLP)" />
                <flux:input wire:model="dia_vencimiento" type="number" label="Día de vencimiento" />
                <flux:input wire:model="descuento_hermanos_pct" type="number" label="Descuento hermanos (%)" required />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarConfig', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
