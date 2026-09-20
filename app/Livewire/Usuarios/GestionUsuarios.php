<?php

namespace App\Livewire\Usuarios;

use App\Concerns\ProfileValidationRules;
use App\Livewire\Concerns\ConTabla;
use App\Models\CargoRango;
use App\Models\Grupo;
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
    use ConTabla, ProfileValidationRules;

    /** Id del usuario en edición (null = creando). */
    public ?int $editandoId = null;

    public string $name = '';

    public string $email = '';

    public ?string $telefono = null;

    public string $rol = '';

    public ?string $rango_id = '';

    /**
     * Sedes asignadas al usuario (una persona puede estar a cargo de varias).
     *
     * @var array<int, string>
     */
    public array $sedes = [];

    public ?string $grupo_id = '';

    public bool $activo = true;

    public bool $mostrarModal = false;

    // Confirmación de eliminación
    public bool $mostrarEliminar = false;

    public ?int $eliminandoId = null;

    public string $eliminandoNombre = '';

    /**
     * Indica si el usuario autenticado es admin-plataforma (ve/asigna todos los grupos).
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
            'sedes' => ['array'],
            'sedes.*' => [Rule::exists('sedes', 'id')],
            // El grupo solo la elige el admin-plataforma; el resto usa la suya.
            'grupo_id' => [$this->esSuperAdmin() ? 'required' : 'nullable', Rule::exists('grupos', 'id')],
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
     * Grupo efectiva del formulario (elegida por admin-plataforma o la propia).
     */
    protected function grupoEfectiva(): ?int
    {
        $id = $this->esSuperAdmin() ? $this->grupo_id : Auth::user()->grupo_id;

        return $id !== '' && $id !== null ? (int) $id : null;
    }

    /**
     * Abre el modal para crear un usuario.
     */
    public function nuevo(): void
    {
        $this->reset('editandoId', 'name', 'email', 'telefono', 'rol', 'rango_id', 'sedes', 'grupo_id');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->grupo_id = (string) Auth::user()->grupo_id;
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Al cambiar de grupo (admin-plataforma) se limpian las sedes elegidas:
     * pertenecen a el grupo anterior y ya no serían válidas.
     */
    public function updatedGrupoId(): void
    {
        $this->sedes = [];
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
        $this->sedes = $usuario->sedes->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->grupo_id = (string) ($usuario->grupo_id ?? '');
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

        $datos = $this->validate();

        $grupoId = $this->grupoEfectiva();

        if ($this->editandoId) {
            $usuario = User::findOrFail($this->editandoId);
            $usuario->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'rango_id' => $datos['rango_id'],
                'grupo_id' => $grupoId,
                'activo' => $this->activo,
            ]);

            Flux::toast(variant: 'success', text: 'Usuario actualizado.');
        } else {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'rango_id' => $datos['rango_id'],
                'grupo_id' => $grupoId,
                'activo' => $this->activo,
                // Contraseña aleatoria inutilizable; el usuario define la suya
                // con el enlace de restablecimiento que se envía a continuación.
                'password' => Str::random(40),
            ]);

            $this->enviarEnlaceContrasena($usuario);
            Flux::toast(variant: 'success', text: 'Usuario creado. Se le envió un enlace para definir su contraseña.');
        }

        $usuario->syncRoles([$datos['rol']]);
        $usuario->sedes()->sync($this->sedes);

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
     * como instructor, clases a su cargo, o matrículas que aceptó).
     * Se consulta con DB directo para ignorar el aislamiento por grupo.
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
            // Las graduaciones ya no acreditan a una cuenta (el crédito va a la
            // persona); solo queda el examinador en inscripciones.
            ['inscripciones', 'instructor_id'],
            ['pagos', 'registrado_por'],
            ['asistencias', 'registrado_por'],
            ['matriculas', 'aceptado_por_user_id'],
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

        // Se limpian vínculos sin valor histórico y se elimina. El progreso de
        // estudio NO se toca: cuelga de la persona, no de la cuenta.
        $usuario->sedes()->detach();
        $usuario->syncRoles([]);
        $usuario->delete();

        Flux::toast(variant: 'success', text: 'Usuario eliminado.');
        $this->mostrarEliminar = false;
        $this->reset('eliminandoId', 'eliminandoNombre');
    }

    public function render()
    {
        $grupoFormulario = $this->grupoEfectiva();

        $consulta = $this->aplicarBusqueda(User::with('roles'), ['name', 'email', 'telefono']);
        $usuarios = $this->aplicarOrden($consulta, ['name', 'email', 'activo'], 'name')->get();

        return view('livewire.usuarios.gestion-usuarios', [
            'usuarios' => $usuarios,
            'idsConHistorial' => $this->idsConHistorial($usuarios->pluck('id')),
            'usuarioActualId' => Auth::id(),
            'roles' => $this->rolesDisponibles(),
            'rangos' => CargoRango::orderBy('nivel')->get(),
            'grupos' => Grupo::orderBy('nombre')->get(),
            'listaSedes' => $grupoFormulario
                ? Sede::sinGrupo()->where('grupo_id', $grupoFormulario)->orderBy('nombre')->get()
                : collect(),
        ]);
    }
}
