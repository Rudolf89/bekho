<?php

namespace App\Livewire\Usuarios;

use App\Concerns\ProfileValidationRules;
use App\Models\Academia;
use App\Models\CargoRango;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Gestión de usuarios')]
class GestionUsuarios extends Component
{
    use ProfileValidationRules;

    /** Id del usuario en edición (null = creando). */
    public ?int $editandoId = null;

    public string $name = '';

    public string $email = '';

    public ?string $telefono = null;

    public string $rol = '';

    public ?int $rango_id = null;

    public ?int $sede_id = null;

    public ?int $academia_id = null;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * Indica si el usuario autenticado es super-admin (ve/asigna todas las academias).
     */
    public function esSuperAdmin(): bool
    {
        return Auth::user()->hasRole('super-admin');
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
            // La academia solo la elige el super-admin; el resto usa la suya.
            'academia_id' => [$this->esSuperAdmin() ? 'required' : 'nullable', Rule::exists('academias', 'id')],
        ];
    }

    /**
     * Roles que el usuario autenticado puede asignar.
     * Un no super-admin no puede crear super-admins.
     *
     * @return list<string>
     */
    public function rolesDisponibles(): array
    {
        $roles = Role::orderBy('name')->pluck('name');

        if (! $this->esSuperAdmin()) {
            $roles = $roles->reject(fn (string $r) => $r === 'super-admin');
        }

        return $roles->values()->all();
    }

    /**
     * Academia efectiva del formulario (elegida por super-admin o la propia).
     */
    protected function academiaEfectiva(): ?int
    {
        return $this->esSuperAdmin() ? $this->academia_id : Auth::user()->academia_id;
    }

    /**
     * Abre el modal para crear un usuario.
     */
    public function nuevo(): void
    {
        $this->reset('editandoId', 'name', 'email', 'telefono', 'rol', 'rango_id', 'sede_id', 'academia_id');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->academia_id = Auth::user()->academia_id;
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
        $this->rango_id = $usuario->rango_id;
        $this->sede_id = $usuario->sedes->first()?->id;
        $this->academia_id = $usuario->academia_id;
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

    public function render()
    {
        $academiaFormulario = $this->academiaEfectiva();

        return view('livewire.usuarios.gestion-usuarios', [
            'usuarios' => User::with('roles')->orderBy('name')->get(),
            'roles' => $this->rolesDisponibles(),
            'rangos' => CargoRango::orderBy('nivel')->get(),
            'academias' => Academia::orderBy('nombre')->get(),
            'sedes' => $academiaFormulario
                ? Sede::sinAcademia()->where('academia_id', $academiaFormulario)->orderBy('nombre')->get()
                : collect(),
        ]);
    }
}
