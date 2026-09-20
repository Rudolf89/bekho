<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Pagos</flux:heading>
            <flux:text class="mt-1">Registro de pagos, verificación y estado de morosidad</flux:text>
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
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Alumno</flux:table.column>
                    <flux:table.column>Grupo</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($morosos as $moroso)
                        <flux:table.row wire:key="moroso-{{ $moroso->id }}">
                            <flux:table.cell variant="strong">{{ $moroso->persona?->nombreCompleto() ?? '—' }}</flux:table.cell>
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
    </div>

    {{-- Pagos --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="lg">Pagos</flux:heading>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-tabla.buscador placeholder="Buscar por quien pagó o referencia…" />
            <flux:select wire:model.live="filtroEstado" placeholder="Todos los estados" class="w-full sm:w-48">
                <flux:select.option value="">Todos los estados</flux:select.option>
                @foreach ($estados as $e)
                    <flux:select.option value="{{ $e->value }}">{{ $e->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <x-tabla.resumen
        :total="$totalPagos"
        etiqueta="pago"
        :plural="'pagos'"
        :sumas="[['etiqueta' => 'Total verificado', 'valor' => '$'.number_format($sumaPagos, 0, ',', '.')]]"
    />

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'fecha_pago'" :direction="$ordenDir" wire:click="ordenarPor('fecha_pago')">Fecha</flux:table.column>
                <flux:table.column>Pagó</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'monto'" :direction="$ordenDir" wire:click="ordenarPor('monto')">Monto</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($pagos as $pago)
                    <flux:table.row wire:key="pago-{{ $pago->id }}">
                        <flux:table.cell>{{ $pago->fecha_pago->format('d-m-Y') }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $pago->pagadoPor?->nombreCompleto() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$pago->estado->color()" size="sm">{{ $pago->estado->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>${{ number_format($pago->monto, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                @if ($pago->estado === \App\Enums\EstadoPago::PorVerificar)
                                    <flux:button wire:click="verificar({{ $pago->id }})" size="sm" variant="ghost" icon="check">Verificar</flux:button>
                                @endif
                                @if ($pago->estado !== \App\Enums\EstadoPago::Anulado)
                                    <flux:button wire:click="abrirAnular({{ $pago->id }})" size="sm" variant="ghost" icon="x-mark">Anular</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="py-4 text-center">
                                {{ $buscar !== '' || $filtroEstado !== '' ? 'Sin resultados para tu filtro.' : 'Aún no hay pagos registrados.' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>{{ $pagos->links() }}</div>

    {{-- Modal registrar pago --}}
    <flux:modal name="pago-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="registrarPago" class="space-y-5">
            <flux:heading size="lg">Registrar pago</flux:heading>
            <flux:text>El monto se aplica a los cargos pendientes del alumno, del más antiguo al más nuevo.</flux:text>

            <flux:select wire:model="pagoMatriculaId" label="Alumno" placeholder="Selecciona">
                @foreach ($matriculas as $m)
                    <flux:select.option value="{{ $m->id }}">{{ $m->persona?->nombreCompleto() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="pagoMonto" type="number" label="Monto (CLP)" required />
                <flux:input wire:model="pagoFechaPago" type="date" label="Fecha de pago" required />
                <flux:input wire:model="pagoBanco" label="Banco (opcional)" />
                <flux:input wire:model="pagoReferencia" label="Referencia (opcional)" placeholder="N° transferencia" />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Registrar</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal anular pago --}}
    <flux:modal name="anular-modal" wire:model="mostrarAnular" class="max-w-md md:min-w-md">
        <form wire:submit="anular" class="space-y-5">
            <flux:heading size="lg">Anular pago</flux:heading>
            <flux:text>El pago no se borra: queda anulado con el motivo y los cargos que cubría vuelven a pendientes.</flux:text>

            <flux:input wire:model="motivoAnulacion" label="Motivo" required />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarAnular', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="danger">Anular</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal configuración --}}
    <flux:modal name="config-modal" wire:model="mostrarConfig" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardarConfig" class="space-y-5">
            <flux:heading size="lg">Configuración de pagos</flux:heading>
            <flux:text>Valores de este grupo. Déjalos en blanco si aún no los defines.</flux:text>

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
