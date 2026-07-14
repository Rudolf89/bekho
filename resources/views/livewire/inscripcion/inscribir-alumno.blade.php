<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Inscripción de alumno nuevo</flux:heading>
            <flux:text class="mt-1">Completa la ficha del alumno. Los campos con * son obligatorios.</flux:text>
        </div>
        <flux:button :href="route('estudiantes.index')" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
    </div>

    <form wire:submit="inscribir" class="space-y-6">
        {{-- Lugar de entrenamiento --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Lugar de entrenamiento</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="sede_id" label="Lugar de entrenamiento *" placeholder="Selecciona la sede">
                    @foreach ($sedes as $sede)
                        <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="instructor_id" label="Instructor a cargo *" placeholder="Selecciona el instructor">
                    @foreach ($instructores as $instructor)
                        <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Datos del alumno --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Datos del alumno</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="nombres" label="Nombres del alumno *" description="Ingresar ambos nombres" required />
                <div class="grid grid-cols-2 gap-2">
                    <flux:input wire:model="apellido_paterno" label="Apellido paterno *" required />
                    <flux:input wire:model="apellido_materno" label="Apellido materno *" required />
                </div>
                <flux:input wire:model="rut" label="RUT del alumno *" placeholder="xxxxxxxx-x (sin puntos)" required />
                <flux:input wire:model.live="fecha_nacimiento" type="date" label="Fecha de nacimiento *" required />
                <div>
                    <flux:label>Género *</flux:label>
                    <flux:radio.group wire:model="genero" class="mt-2 flex gap-6">
                        @foreach ($generos as $g)
                            <flux:radio value="{{ $g->value }}" label="{{ $g->etiqueta() }}" />
                        @endforeach
                    </flux:radio.group>
                    @error('genero') <flux:text size="sm" class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror
                </div>
                <flux:select wire:model="grupo_etario" label="Grupo etario *" placeholder="Selecciona">
                    @foreach ($grupos as $grupo)
                        <flux:select.option value="{{ $grupo->value }}">{{ $grupo->etiqueta() }} ({{ $grupo->rangoEdad() }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:text size="sm" class="mt-2 text-zinc-500">El grupo etario se sugiere por la edad; el instructor puede ajustarlo.</flux:text>
        </div>

        {{-- Domicilio --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Domicilio</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="direccion" label="Dirección *" class="sm:col-span-2" required />
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
            </div>
        </div>

        {{-- Apoderados y contacto --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Apoderados y contacto</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="apoderado_1" label="Nombre apoderado 1" description="Solamente si aplica" />
                <flux:input wire:model="apoderado_2" label="Nombre apoderado 2" description="Solamente si aplica" />
                <flux:input wire:model="telefono_contacto" label="Teléfono de contacto 1 *" placeholder="9xxxxxxxx" required />
                <flux:input wire:model="telefono_contacto_2" label="Teléfono de contacto 2" placeholder="9xxxxxxxx" />
                <flux:input wire:model="email_contacto" type="email" label="Correo electrónico 1 *" required />
                <flux:input wire:model="email_contacto_2" type="email" label="Correo electrónico 2" />
            </div>
        </div>

        {{-- Pago y declaración --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Mensualidad y declaración</flux:heading>
            <div class="space-y-4">
                <flux:select wire:model="dia_vencimiento" label="Día de vencimiento de las mensualidades *" placeholder="Elegir día" class="sm:max-w-xs">
                    @for ($dia = 1; $dia <= 31; $dia++)
                        <flux:select.option value="{{ $dia }}">{{ $dia }}</flux:select.option>
                    @endfor
                </flux:select>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox wire:model="acepto_reglamento">
                        <span>
                            Declaro haber leído, conocer y aceptar en todas sus partes el
                            @if (config('bekho.reglamento_url'))
                                <flux:link href="{{ config('bekho.reglamento_url') }}" target="_blank">Reglamento del Alumno BEKHO Martial Arts</flux:link>,
                            @else
                                «Reglamento del Alumno BEKHO Martial Arts»,
                            @endif
                            y me comprometo a cumplir todas sus normas sin excepciones. *
                        </span>
                    </flux:checkbox>
                    @error('acepto_reglamento') <flux:text size="sm" class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="outline" :href="route('estudiantes.index')" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="user-plus">Inscribir alumno</flux:button>
        </div>
    </form>
</div>
