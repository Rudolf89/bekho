<?php

namespace App\Models;

// Decisión de diseño (seguridad): NO se implementa MustVerifyEmail. La escuela
// controla las altas y muchos alumnos (menores) no tienen correo propio, así que
// no se exige verificación de email de forma global. Por eso también se quitó el
// middleware 'verified' de las rutas. Para activarla en el futuro: implementar
// aquí `implements MustVerifyEmail` y volver a poner 'verified' en las rutas.
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EstadoProgreso;
use App\Models\Concerns\PerteneceGrupo;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $persona_id
 * @property int|null $grupo_id
 * @property int|null $rango_id
 * @property int|null $supervisor_id
 * @property string|null $telefono
 * @property bool $activo
 */
#[Fillable(['name', 'email', 'password', 'persona_id', 'grupo_id', 'rango_id', 'supervisor_id', 'telefono', 'activo'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, PerteneceGrupo, TwoFactorAuthenticatable;

    /**
     * La cuenta es identidad/acceso y se resuelve ANTES de que exista un tenant
     * (login, recuperación de sesión, reset de contraseña): la autenticación no
     * conoce el grupo hasta conocer al usuario. Por eso User NO falla cerrado sin
     * grupo activo (cuando hay grupo activo, el aislamiento por grupo sí se aplica
     * en los listados, como en cualquier modelo). Es la excepción a la regla de
     * fallar cerrado; no se pudo resolver con comoSistema() porque la resolución
     * del usuario ocurre dentro del guard del framework.
     */
    public function fallaCerradoSinGrupo(): bool
    {
        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Persona (identidad) dueña de esta cuenta de acceso.
     *
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Grupo a la que pertenece el usuario.
     *
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Rango/cargo del usuario.
     *
     * @return BelongsTo<CargoRango, $this>
     */
    public function rango(): BelongsTo
    {
        return $this->belongsTo(CargoRango::class, 'rango_id');
    }

    /**
     * Supervisor directo del usuario.
     *
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Usuarios supervisados por este usuario.
     *
     * @return HasMany<User, $this>
     */
    public function supervisados(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Sedes en las que el usuario es instructor.
     *
     * @return BelongsToMany<Sede, $this>
     */
    public function sedes(): BelongsToMany
    {
        return $this->belongsToMany(Sede::class, 'sede_user');
    }

    /**
     * Clases en las que el usuario participa como instructor (cualquier papel).
     *
     * @return BelongsToMany<Clase, $this>
     */
    public function clases(): BelongsToMany
    {
        return $this->belongsToMany(Clase::class, 'clase_instructor')
            ->withPivot('papel')
            ->withTimestamps();
    }

    /**
     * Indica si el usuario solo tiene acceso de lectura (federación): puede ver
     * datos de todos los grupos pero no crear ni modificar.
     */
    public function esSoloLectura(): bool
    {
        return $this->hasRole('federacion');
    }

    /**
     * Progreso de estudio del usuario, vía su persona (el progreso cuelga de la
     * persona, no del user: los menores no tienen cuenta).
     *
     * @return HasMany<ProgresoContenido, $this>
     */
    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoContenido::class, 'persona_id', 'persona_id');
    }

    /**
     * Estado de progreso del usuario (por su persona) en un contenido.
     */
    public function progresoEn(Contenido $contenido): EstadoProgreso
    {
        if ($this->persona_id === null) {
            return EstadoProgreso::Pendiente;
        }

        $progreso = $this->progresos()
            ->where('contenido_id', $contenido->id)
            ->first();

        return $progreso === null ? EstadoProgreso::Pendiente : $progreso->estado;
    }

    /**
     * Sedes a las que el usuario queda acotado por sus roles de personal
     * (personal_grupo_rol con sede_id) en el grupo activo. Devuelve null cuando NO
     * hay restricción por sede: no es personal del grupo, o tiene algún rol a
     * nivel de grupo (sin sede). Con esto las policies y los listados limitan a un
     * direccion-sede a su sede, sin activar el modo teams de spatie.
     *
     * @return list<int>|null
     */
    public function sedesRestringidas(): ?array
    {
        if ($this->persona_id === null) {
            return null;
        }

        $grupoId = \App\Support\Tenancy\Grupo::id() ?? $this->grupo_id;
        if ($grupoId === null) {
            return null;
        }

        $personal = PersonalGrupo::withoutGlobalScopes()
            ->where('persona_id', $this->persona_id)
            ->where('grupo_id', $grupoId)
            ->first();

        $roles = $personal?->roles()->get();
        if ($roles === null || $roles->isEmpty()) {
            return null;
        }

        // Un rol a nivel de grupo (sin sede) implica acceso a todo el grupo.
        if ($roles->contains(fn (PersonalGrupoRol $r) => $r->sede_id === null)) {
            return null;
        }

        $sedes = array_values(
            $roles
                ->filter(fn (PersonalGrupoRol $r) => $r->sede_id !== null)
                ->map(fn (PersonalGrupoRol $r) => (int) $r->sede_id)
                ->unique()
                ->all()
        );

        return $sedes === [] ? null : $sedes;
    }
}
