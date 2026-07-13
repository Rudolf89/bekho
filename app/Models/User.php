<?php

namespace App\Models;

// Decisión de diseño (seguridad): NO se implementa MustVerifyEmail. La escuela
// controla las altas y muchos alumnos (menores) no tienen correo propio, así que
// no se exige verificación de email de forma global. Por eso también se quitó el
// middleware 'verified' de las rutas. Para activarla en el futuro: implementar
// aquí `implements MustVerifyEmail` y volver a poner 'verified' en las rutas.
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EstadoProgreso;
use App\Models\Concerns\PerteneceAcademia;
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
 * @property int|null $academia_id
 * @property int|null $rango_id
 * @property int|null $supervisor_id
 * @property string|null $telefono
 * @property bool $activo
 */
#[Fillable(['name', 'email', 'password', 'academia_id', 'rango_id', 'supervisor_id', 'telefono', 'activo'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, PerteneceAcademia, TwoFactorAuthenticatable;

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
     * Academia a la que pertenece el usuario.
     *
     * @return BelongsTo<Academia, $this>
     */
    public function academia(): BelongsTo
    {
        return $this->belongsTo(Academia::class);
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
     * Registros de progreso de formación del usuario.
     *
     * @return HasMany<ProgresoContenido, $this>
     */
    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoContenido::class);
    }

    /**
     * Estado de progreso del usuario en un contenido (Pendiente si no hay registro).
     */
    public function progresoEn(Contenido $contenido): EstadoProgreso
    {
        return $this->progresos()
            ->where('contenido_id', $contenido->id)
            ->first()?->estado ?? EstadoProgreso::Pendiente;
    }
}
