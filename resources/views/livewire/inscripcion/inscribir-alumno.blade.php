<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">Inscripción de alumno nuevo</flux:heading>
        <flux:text class="mt-1">Completa la ficha del alumno. Los campos con <span class="font-semibold text-red-500">*</span> son obligatorios.</flux:text>
    </div>

    <form wire:submit="inscribir" class="space-y-6">
        {{-- 1. Lugar de entrenamiento --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Lugar de entrenamiento</flux:heading>
            <flux:text size="sm" class="mb-4 mt-0.5 text-zinc-500">Dónde asiste a sus entrenamientos regulares.</flux:text>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="sede_id" label="Sede *" placeholder="Selecciona la sede">
                    @foreach ($sedes as $sede)
                        <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="instructor_id" label="Instructor a cargo *"
                    :placeholder="$sede_id ? 'Selecciona el instructor' : 'Primero elige la sede'"
                    :disabled="! $sede_id">
                    @forelse ($instructores as $instructor)
                        <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
                    @empty
                        @if ($sede_id)
                            <flux:select.option value="" disabled>Esta sede no tiene instructores asignados</flux:select.option>
                        @endif
                    @endforelse
                </flux:select>
            </div>
        </section>

        {{-- 2. Datos del alumno --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Datos del alumno</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="rut" label="RUT *" placeholder="12345678-9 (sin puntos)" />
                <flux:input wire:model="nombres" label="Nombres *" placeholder="Ambos nombres" />
                <flux:input wire:model="apellido_paterno" label="Apellido paterno *" />
                <flux:input wire:model="apellido_materno" label="Apellido materno *" />
                <flux:input wire:model.live="fecha_nacimiento" type="date" label="Fecha de nacimiento *"
                    :description="$this->edad() !== null ? 'Edad: '.$this->edad().' '.($this->edad() === 1 ? 'año' : 'años') : null" />
                <flux:select wire:model.live="grupo_etario" label="Grupo etario *" placeholder="Selecciona">
                    @foreach ($grupos as $grupo)
                        <flux:select.option value="{{ $grupo->value }}">{{ $grupo->etiqueta() }} ({{ $grupo->rangoEdad() }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:field>
                    <flux:label>Género *</flux:label>
                    <flux:radio.group wire:model="genero" variant="segmented">
                        @foreach ($generos as $g)
                            <flux:radio value="{{ $g->value }}">{{ $g->etiqueta() }}</flux:radio>
                        @endforeach
                    </flux:radio.group>
                    <flux:error name="genero" />
                </flux:field>
            </div>
            <flux:text size="sm" class="mt-3 text-zinc-500">El grupo etario se sugiere por la edad al ingresar la fecha de nacimiento; el instructor puede ajustarlo.</flux:text>

            {{-- Aviso cuando el alumno está por cumplir la edad del grupo siguiente:
                 se ofrece pasarlo de una vez (el campo igual es editable a mano). --}}
            @php($sugerencia = $this->sugerenciaProximoGrupo())
            @if ($sugerencia)
                <flux:callout size="sm" icon="arrow-trending-up" color="amber" class="mt-3">
                    <flux:callout.text>
                        Está por cumplir {{ $sugerencia['edadProxima'] }} años{{ $sugerencia['meses'] > 0 ? ' (en ~'.$sugerencia['meses'].' '.($sugerencia['meses'] === 1 ? 'mes' : 'meses').')' : ' este mes' }}.
                        Si corresponde, puedes inscribirlo directamente en <strong>{{ $sugerencia['grupo']->etiqueta() }}</strong> ({{ $sugerencia['grupo']->rangoEdad() }}).
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="ghost" icon="arrow-up-right"
                            wire:click="cambiarGrupo('{{ $sugerencia['grupo']->value }}')">
                            Pasar a {{ $sugerencia['grupo']->etiqueta() }}
                        </flux:button>
                    </x-slot>
                </flux:callout>
            @endif
        </section>

        {{-- 3. Domicilio --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Domicilio</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="region" label="Región *" placeholder="Elegir región">
                    @foreach ($regiones as $region)
                        <flux:select.option value="{{ $region }}">{{ $region }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="comuna" label="Comuna *" :placeholder="$region ? 'Elegir comuna' : 'Primero elige la región'">
                    @foreach ($comunasRegion as $comuna)
                        <flux:select.option value="{{ $comuna }}">{{ $comuna }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="direccion" label="Dirección *" placeholder="Calle, número, depto." class="sm:col-span-2" />
            </div>
        </section>

        {{-- 4. Apoderados y contacto --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Apoderados y contacto</flux:heading>
            <flux:text size="sm" class="mb-4 mt-0.5 text-zinc-500">
                El apoderado 1 es obligatorio para Tigers y For Kids.
            </flux:text>

            {{-- Apoderado 1 (obligatorio según grupo) con su contacto. --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="apoderado_1" :label="$this->requiereApoderado() ? 'Nombre apoderado 1 *' : 'Nombre apoderado 1 (opcional)'" class="sm:col-span-2" />
                <flux:input wire:model="telefono_contacto" label="Teléfono 1 *" placeholder="9xxxxxxxx" />
                <flux:input wire:model="email_contacto" type="email" label="Correo 1 *" placeholder="correo@ejemplo.cl" />
            </div>

            {{-- Segundo apoderado: opcional, se despliega con la casilla. --}}
            <flux:separator class="my-5" />
            <flux:checkbox wire:model.live="agregarApoderado2" label="Agregar un segundo apoderado" />

            @if ($agregarApoderado2)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="apoderado_2" label="Nombre apoderado 2" class="sm:col-span-2" />
                    <flux:input wire:model="telefono_contacto_2" label="Teléfono 2" placeholder="9xxxxxxxx" />
                    <flux:input wire:model="email_contacto_2" type="email" label="Correo 2" placeholder="correo@ejemplo.cl" />
                </div>
            @endif
        </section>

        {{-- 5. Mensualidad y reglamento --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Mensualidad y reglamento</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="dia_vencimiento" label="Día de vencimiento de la mensualidad *" placeholder="Elegir día">
                    @foreach ($diasVencimiento as $dia)
                        <flux:select.option value="{{ $dia }}">Día {{ $dia }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:separator class="my-5" />

            <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900/50">
                @if (config('bekho.reglamento_url'))
                    <flux:button :href="config('bekho.reglamento_url')" target="_blank" icon="document-text" variant="ghost" size="sm">
                        Leer el Reglamento del Alumno (PDF)
                    </flux:button>
                @endif
                {{-- El checkbox con label ya auto-renderiza su error; no se agrega
                     un <flux:error> aparte para no duplicar el mensaje. --}}
                <flux:checkbox wire:model="acepto_reglamento"
                    label="Declaro haber leído, conocer y aceptar en todas sus partes el «Reglamento del Alumno BEKHO Martial Arts», y me comprometo a cumplir todas sus normas sin excepciones. *" />
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" :href="route('estudiantes.index')" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="user-plus">Inscribir alumno</flux:button>
        </div>
    </form>
</div>
