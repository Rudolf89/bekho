<?php

namespace App\Livewire\Usuarios;

use App\Concerns\ProfileValidationRules;
use App\Livewire\Concerns\ConOrden;
use App\Models\Academia;
use App\Models\CargoRango;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Gestión de usuarios')]
class GestionUsuarios extends Component
{
    use ConOrden, ProfileValidationRules;

    /** Id del usuario en edición (null = creando). */
    public ?int $editandoId = null;

    public string $name = '';

    public string $email = '';

    public ?string $telefono = null;

    public string $rol = '';

    public ?string $rango_id = '';

    public ?string $sede_id = '';

    public ?string $academia_id = '';

    public bool $activo = true;

    public bool $mostrarModal = false;

    // Confirmación de eliminación
    public bool $mostrarEliminar = false;

    public ?int $eliminandoId = null;

    public string $eliminandoNombre = '';

    /**
     * Indica si el usuario autenticado es admin-plataforma (ve/asigna todas las academias).
     */
    public function esSuperAdmin(): bool
    {
        return Auth::user()->hasRole('admin-plataforma');
    }

    /**
     * Reglas de validación del formulario.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->editandoId),
            'telefono' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', Rule::in($this->rolesDisponibles())],
            'rango_id' => ['nullable', Rule::exists('cargos_rangos', 'id')],
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')],
            // La academia solo la elige el admin-plataforma; el resto usa la suya.
            'academia_id' => [$this->esSuperAdmin() ? 'required' : 'nullable', Rule::exists('academias', 'id')],
        ];
    }

    /**
     * Roles que el usuario autenticado puede asignar.
     * Un no admin-plataforma no puede crear admin-plataformas.
     *
     * @return list<string>
     */
    public function rolesDisponibles(): array
    {
        $roles = Role::orderBy('name')->pluck('name');

        if (! $this->esSuperAdmin()) {
            $roles = $roles->reject(fn (string $r) => $r === 'admin-plataforma');
        }

        return $roles->values()->all();
    }

    /**
     * Academia efectiva del formulario (elegida por admin-plataforma o la propia).
     */
    protected function academiaEfectiva(): ?int
    {
        $id = $this->esSuperAdmin() ? $this->academia_id : Auth::user()->academia_id;

        return $id !== '' && $id !== null ? (int) $id : null;
    }

    /**
     * Abre el modal para crear un usuario.
     */
    public function nuevo(): void
    {
        $this->reset('editandoId', 'name', 'email', 'telefono', 'rol', 'rango_id', 'sede_id', 'academia_id');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->academia_id = (string) Auth::user()->academia_id;
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Abre el modal para editar un usuario.
     */
    public function editar(User $usuario): void
    {
        $this->editandoId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->telefono = $usuario->telefono;
        $this->rol = $usuario->roles->first()?->name ?? '';
        $this->rango_id = (string) ($usuario->rango_id ?? '');
        $this->sede_id = (string) ($usuario->sedes->first()?->id ?? '');
        $this->academia_id = (string) ($usuario->academia_id ?? '');
        $this->activo = $usuario->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Crea o actualiza el usuario. Al crear, envía un enlace para que el usuario
     * defina su propia contraseña (nunca se asignan contraseñas en texto plano).
     */
    public function guardar(): void
    {
        // Los <select> opcionales devuelven '' cuando no se elige nada; se
        // normaliza a null antes de validar/guardar.
        $this->rango_id = $this->rango_id ?: null;
        $this->sede_id = $this->sede_id ?: null;

        $datos = $this->validate();

        $academiaId = $this->academiaEfectiva();

        if ($this->editandoId) {
            $usuario = User::findOrFail($this->editandoId);
            $usuario->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'rango_id' => $datos['rango_id'],
                'academia_id' => $academiaId,
                'activo' => $this->activo,
            ]);

            Flux::toast(variant: 'success', text: 'Usuario actualizado.');
        } else {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'rango_id' => $datos['rango_id'],
                'academia_id' => $academiaId,
                'activo' => $this->activo,
                // Contraseña aleatoria inutilizable; el usuario define la suya
                // con el enlace de restablecimiento que se envía a continuación.
                'password' => Str::random(40),
            ]);

            $this->enviarEnlaceContrasena($usuario);
            Flux::toast(variant: 'success', text: 'Usuario creado. Se le envió un enlace para definir su contraseña.');
        }

        $usuario->syncRoles([$datos['rol']]);
        $usuario->sedes()->sync($this->sede_id ? [$this->sede_id] : []);

        $this->mostrarModal = false;
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function alternarActivo(User $usuario): void
    {
        $usuario->update(['activo' => ! $usuario->activo]);
    }

    /**
     * Envía (o reenvía) el enlace para definir/restablecer la contraseña.
     * Reutiliza el flujo de restablecimiento de contraseña de Fortify.
     */
    public function enviarEnlaceContrasena(User $usuario): void
    {
        Password::sendResetLink(['email' => $usuario->email]);

        Flux::toast(text: "Se envió el enlace de contraseña a {$usuario->email}.");
    }

    /**
     * Ids de usuarios (dentro de los indicados) con historial en el sistema.
     *
     * Un usuario con historial no se puede eliminar sin corromper registros
     * (pagos/asistencia registrados, graduaciones e inscripciones acreditadas
     * como instructor, clases a su cargo, o vínculo como apoderado o alumno).
     * Se consulta con DB directo para ignorar el aislamiento por academia.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, int>
     */
    protected function idsConHistorial(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $fuentes = [
            ['clases', 'instructor_id'],
            ['graduaciones', 'instructor_id'],
            ['inscripciones', 'instructor_id'],
            ['pagos', 'registrado_por'],
            ['asistencias', 'registrado_por'],
            ['estudiantes', 'user_id'],
            ['apoderado_estudiante', 'user_id'],
        ];

        return collect($fuentes)
            ->flatMap(fn (array $f) => DB::table($f[0])->whereIn($f[1], $ids)->pluck($f[1])->all())
            ->unique()
            ->values();
    }

    /**
     * Abre la confirmación de eliminación, si el usuario se puede eliminar.
     */
    public function confirmarEliminar(User $usuario): void
    {
        if ($usuario->id === Auth::id()) {
            Flux::toast(variant: 'danger', text: 'No puedes eliminar tu propia cuenta.');

            return;
        }

        if ($this->idsConHistorial(collect([$usuario->id]))->isNotEmpty()) {
            Flux::toast(variant: 'warning', text: 'Este usuario tiene historial en el sistema; solo puede desactivarse.');

            return;
        }

        $this->eliminandoId = $usuario->id;
        $this->eliminandoNombre = $usuario->name;
        $this->mostrarEliminar = true;
    }

    /**
     * Elimina definitivamente un usuario sin historial.
     */
    public function eliminar(): void
    {
        if (! $this->eliminandoId) {
            return;
        }

        $usuario = User::findOrFail($this->eliminandoId);

        // Resguardos (revalidados por si algo cambió desde que se abrió el modal).
        if ($usuario->id === Auth::id() || $this->idsConHistorial(collect([$usuario->id]))->isNotEmpty()) {
            Flux::toast(variant: 'warning', text: 'Este usuario ya no se puede eliminar; solo puede desactivarse.');
            $this->mostrarEliminar = false;

            return;
        }

        // Se limpian vínculos sin valor histórico y se elimina.
        $usuario->sedes()->detach();
        $usuario->syncRoles([]);
        $usuario->progresos()->delete();
        $usuario->delete();

        Flux::toast(variant: 'success', text: 'Usuario eliminado.');
        $this->mostrarEliminar = false;
        $this->reset('eliminandoId', 'eliminandoNombre');
    }

    public function render()
    {
        $academiaFormulario = $this->academiaEfectiva();

        $usuarios = $this->aplicarOrden(User::with('roles'), ['name', 'email', 'activo'], 'name')->get();

        return view('livewire.usuarios.gestion-usuarios', [
            'usuarios' => $usuarios,
            'idsConHistorial' => $this->idsConHistorial($usuarios->pluck('id')),
            'usuarioActualId' => Auth::id(),
            'roles' => $this->rolesDisponibles(),
            'rangos' => CargoRango::orderBy('nivel')->get(),
            'academias' => Academia::orderBy('nombre')->get(),
            'sedes' => $academiaFormulario
                ? Sede::sinAcademia()->where('academia_id', $academiaFormulario)->orderBy('nombre')->get()
                : collect(),
        ]);
    }
}
