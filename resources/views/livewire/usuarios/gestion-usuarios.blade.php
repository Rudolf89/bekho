<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Usuarios</flux:heading>
            <flux:text class="mt-1">Alta y gestión de cuentas de la escuela</flux:text>
        </div>
        <flux:button wire:click="nuevo" icon="user-plus" variant="primary">Nuevo usuario</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$ordenCampo === 'name'" :direction="$ordenDir" wire:click="ordenarPor('name')">Nombre</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'email'" :direction="$ordenDir" wire:click="ordenarPor('email')">Correo</flux:table.column>
                <flux:table.column>Rol</flux:table.column>
                <flux:table.column sortable :sorted="$ordenCampo === 'activo'" :direction="$ordenDir" wire:click="ordenarPor('activo')">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($usuarios as $usuario)
                    <flux:table.row wire:key="usuario-{{ $usuario->id }}">
                        <flux:table.cell variant="strong">{{ $usuario->name }}</flux:table.cell>
                        <flux:table.cell>{{ $usuario->email }}</flux:table.cell>
                        <flux:table.cell>
                            @foreach ($usuario->roles as $rol)
                                <flux:badge color="blue" size="sm">{{ $rol->name }}</flux:badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$usuario->activo ? 'green' : 'zinc'" size="sm">
                                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="enviarEnlaceContrasena({{ $usuario->id }})"
                                    icon="envelope" variant="ghost" size="sm" title="Enviar enlace de contraseña" />
                                <flux:button wire:click="editar({{ $usuario->id }})" icon="pencil-square" variant="ghost" size="sm" />
                                <flux:button wire:click="alternarActivo({{ $usuario->id }})"
                                    :icon="$usuario->activo ? 'user-minus' : 'user'" variant="ghost" size="sm"
                                    :title="$usuario->activo ? 'Desactivar' : 'Activar'" />
                                @if ($usuario->id !== $usuarioActualId && ! $idsConHistorial->contains($usuario->id))
                                    {{-- Solo se puede eliminar a usuarios sin historial en el sistema. --}}
                                    <flux:button wire:click="confirmarEliminar({{ $usuario->id }})"
                                        icon="trash" variant="ghost" size="sm" title="Eliminar"
                                        class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="py-4 text-center">Aún no hay usuarios. Crea el primero.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="usuario-modal" wire:model="mostrarModal" class="max-w-lg md:min-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">{{ $editandoId ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>

            <flux:input wire:model="name" label="Nombre" required />
            <flux:input wire:model="email" type="email" label="Correo electrónico" required />
            <flux:input wire:model="telefono" label="Teléfono" />

            <flux:select wire:model="rol" label="Rol" placeholder="Selecciona un rol">
                @foreach ($roles as $r)
                    <flux:select.option value="{{ $r }}">{{ $r }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="rango_id" label="Rango" placeholder="Sin rango">
                @foreach ($rangos as $rango)
                    <flux:select.option value="{{ $rango->id }}">{{ $rango->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->esSuperAdmin())
                <flux:select wire:model.live="academia_id" label="Academia" placeholder="Selecciona una academia">
                    @foreach ($academias as $academia)
                        <flux:select.option value="{{ $academia->id }}">{{ $academia->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            {{-- La dirección administra toda la academia y puede no estar atada a
                 una sede. La opción explícita "Sin sede" permite dejarla en blanco
                 (el placeholder de Flux libre no se puede volver a seleccionar). --}}
            <flux:select wire:model="sede_id" label="Sede" placeholder="Sin sede">
                <flux:select.option value="">Sin sede</flux:select.option>
                @foreach ($sedes as $sede)
                    <flux:select.option value="{{ $sede->id }}">{{ $sede->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="activo" label="Activo" />

            @if (! $editandoId)
                <flux:callout icon="envelope" variant="secondary">
                    <flux:callout.text>
                        Al crear el usuario se le enviará un enlace por email para que defina su contraseña.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="eliminar-usuario" wire:model="mostrarEliminar" class="max-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">¿Eliminar a {{ $eliminandoNombre }}?</flux:heading>
                <flux:text>
                    Esta acción es permanente y no se puede deshacer. Solo es posible porque este
                    usuario no tiene historial (pagos, asistencia, graduaciones ni alumnos asociados).
                    Si prefieres conservar el registro, mejor desactívalo.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="outline" wire:click="$set('mostrarEliminar', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="eliminar">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
