<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:button :href="route('examenes.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a convocatorias
        </flux:button>
    </div>

    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $convocatoria->nombre }}</flux:heading>
            <flux:text class="mt-1">{{ $convocatoria->fecha->format('d-m-Y') }} · {{ $convocatoria->sede?->nombre ?? 'Todas las sedes' }}</flux:text>
        </div>
        @if ($finalizada)
            <flux:badge color="green" size="lg">Finalizada</flux:badge>
        @elsecan('gestionar examenes')
            <flux:button wire:click="finalizar" icon="check-badge" variant="primary"
                wire:confirm="Al finalizar se aplicarán las graduaciones aprobadas y se subirá el grado. ¿Continuar?">
                Finalizar convocatoria
            </flux:button>
        @endif
    </div>

    {{-- Inscritos --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center gap-2 border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="lg">Inscritos</flux:heading>
            <flux:badge size="sm" color="zinc">{{ $inscritos->count() }}</flux:badge>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Estudiante</flux:table.column>
                <flux:table.column>Grado</flux:table.column>
                <flux:table.column>Instructor</flux:table.column>
                <flux:table.column>V°B°</flux:table.column>
                <flux:table.column>Resultado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($inscritos as $ins)
                    <flux:table.row wire:key="ins-{{ $ins->id }}">
                        <flux:table.cell variant="strong">{{ $ins->estudiante?->nombre }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $ins->gradoOrigen?->nombre ?? '—' }} → {{ $ins->gradoDestino?->nombre ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $ins->instructor?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$ins->visto_bueno ? 'green' : 'zinc'" size="sm">
                                {{ $ins->visto_bueno ? 'Sí' : 'No' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($ins->resultado)
                                {{ $ins->resultado->etiqueta() }}@if ($ins->nota) · {{ $ins->nota }}@endif
                            @else
                                <flux:text size="sm">Pendiente</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                @unless ($finalizada)
                                    @can('gestionar examenes')
                                        <flux:button wire:click="abrirEdicion({{ $ins->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                    @endcan
                                    @can('inscribir examenes')
                                        <flux:button wire:click="eliminar({{ $ins->id }})" icon="trash" variant="ghost" size="sm"
                                            title="Quitar inscripción"
                                            wire:confirm="¿Quitar a {{ $ins->estudiante?->nombre }} de esta convocatoria?" />
                                    @endcan
                                @endunless
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center">Sin inscritos todavía.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Sugeridos --}}
    @unless ($finalizada)
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">Sugeridos</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $sugeridos->count() }}</flux:badge>
                </div>
                <flux:text size="sm" class="mt-1">Estudiantes activos de la sede. La marca de elegibilidad es orientativa; tú confirmas.</flux:text>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Estudiante</flux:table.column>
                    <flux:table.column>Meses en grado</flux:table.column>
                    <flux:table.column>Asistencia</flux:table.column>
                    <flux:table.column>Elegible</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($sugeridos as $s)
                        <flux:table.row wire:key="sug-{{ $s['estudiante']->id }}">
                            <flux:table.cell variant="strong">{{ $s['estudiante']->nombre }}</flux:table.cell>
                            <flux:table.cell>{{ $s['meses'] }}</flux:table.cell>
                            <flux:table.cell>{{ $s['asistencia'] !== null ? $s['asistencia'].'%' : '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$s['cumple'] ? 'green' : 'zinc'" size="sm">
                                    {{ $s['cumple'] ? 'Cumple' : 'Revisar' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button wire:click="inscribir({{ $s['estudiante']->id }})" size="sm" variant="ghost" icon="plus">
                                        Inscribir
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <flux:text class="py-4 text-center">No hay estudiantes sugeridos para inscribir.</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endunless

    {{-- Modal edición de inscripción --}}
    <flux:modal name="ins-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardarEdicion" class="space-y-5">
            <flux:heading size="lg">Inscripción</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="ins_grado_destino" label="Grado destino" placeholder="Sin definir">
                    @foreach ($grados as $grado)
                        <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }} ({{ $grado->escala->etiqueta() }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="ins_instructor" label="Instructor a acreditar" placeholder="Sin asignar">
                    @foreach ($instructores as $inst)
                        <flux:select.option value="{{ $inst->id }}">{{ $inst->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="ins_resultado" label="Resultado" placeholder="Pendiente">
                    @foreach ($resultados as $r)
                        <flux:select.option value="{{ $r->value }}">{{ $r->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="ins_nota" type="number" step="0.1" min="9.0" max="9.9" label="Nota (9.0–9.9)" />
            </div>

            <flux:switch wire:model="ins_visto_bueno" label="Visto bueno del instructor" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
