<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:button :href="route('cuestionarios.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
            Volver a cuestionarios
        </flux:button>
    </div>

    <flux:heading size="xl">{{ $cuestionario ? 'Editar cuestionario' : 'Nuevo cuestionario' }}</flux:heading>

    <form wire:submit="guardar" class="space-y-6">
        {{-- Metadatos --}}
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:input wire:model="titulo" label="Título" placeholder="p. ej. Examen de Juez ATA — Nivel 1" />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="2" placeholder="De qué trata la evaluación…" />
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="area" label="Área" placeholder="Arbitraje, Currículo…" />
                <flux:input wire:model="umbral_aprobacion" type="number" min="1" max="100" label="% para aprobar" />
                <div class="flex items-end pb-2">
                    <flux:checkbox wire:model="activo" label="Activo (visible para rendir)" />
                </div>
            </div>
        </div>

        {{-- Preguntas --}}
        <div class="space-y-4">
            @foreach ($preguntas as $i => $pregunta)
                <div wire:key="preg-{{ $i }}" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Pregunta {{ $i + 1 }}</flux:heading>
                        <flux:button type="button" icon="trash" size="xs" variant="subtle" wire:click="eliminarPregunta({{ $i }})"
                            title="Eliminar pregunta" wire:confirm="¿Eliminar esta pregunta y sus opciones?" />
                    </div>

                    <flux:textarea wire:model="preguntas.{{ $i }}.enunciado" label="Enunciado" rows="2" />
                    @error("preguntas.$i.enunciado") <flux:text size="xs" class="text-red-600">{{ $message }}</flux:text> @enderror

                    <div class="space-y-2">
                        <flux:text size="sm" class="font-semibold">Opciones (marca las correctas)</flux:text>
                        @error("preguntas.$i.correcta") <flux:text size="xs" class="text-red-600">{{ $message }}</flux:text> @enderror

                        @foreach ($pregunta['opciones'] as $j => $opcion)
                            <div wire:key="preg-{{ $i }}-op-{{ $j }}" class="flex items-center gap-2">
                                <flux:checkbox wire:model="preguntas.{{ $i }}.opciones.{{ $j }}.correcta" />
                                <flux:input wire:model="preguntas.{{ $i }}.opciones.{{ $j }}.texto" class="flex-1" placeholder="Texto de la opción" />
                                <flux:button type="button" icon="x-mark" size="xs" variant="subtle" wire:click="eliminarOpcion({{ $i }}, {{ $j }})" />
                            </div>
                        @endforeach

                        <flux:button type="button" icon="plus" size="xs" variant="subtle" wire:click="agregarOpcion({{ $i }})">
                            Agregar opción
                        </flux:button>
                    </div>

                    <flux:textarea wire:model="preguntas.{{ $i }}.explicacion" label="Explicación (por qué)" rows="2" placeholder="Se muestra al corregir. Opcional." />
                    <flux:input wire:model="preguntas.{{ $i }}.nota" label="Nota / advertencia" placeholder="Opcional (p. ej. verificar con el reglamento)." />
                </div>
            @endforeach

            <flux:button type="button" icon="plus" variant="filled" wire:click="agregarPregunta">
                Agregar pregunta
            </flux:button>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('cuestionarios.index')" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Guardar cuestionario</flux:button>
        </div>
    </form>
</div>
