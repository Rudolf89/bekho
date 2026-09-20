<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Enums\Genero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Identidad única de una persona en la federación (por encima del grupo).
 * Una misma persona puede ser alumna, apoderada, personal o instructora.
 */
class Persona extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'genero',
        'telefono',
        'email',
        'direccion',
        'comuna',
        'region',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'contacto_emergencia_relacion',
        'grado_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'genero' => Genero::class,
        ];
    }

    /**
     * Nombre completo (nombres + apellidos disponibles).
     */
    public function nombreCompleto(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));
    }

    /**
     * Edad en años cumplidos a la fecha dada (hoy por defecto).
     */
    public function edad(?\DateTimeInterface $a = null): int
    {
        return (int) $this->fecha_nacimiento->diffInYears($a ?? now());
    }

    /**
     * ¿Es mayor de edad (18+)? Solo los mayores pueden tener cuenta (users) y
     * ser apoderados.
     */
    public function esMayorDeEdad(?\DateTimeInterface $a = null): bool
    {
        return $this->edad($a) >= 18;
    }

    /**
     * Grado marcial actual (caché de la última graduación).
     *
     * @return BelongsTo<Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    /**
     * Documentos de identidad (RUT, pasaporte).
     *
     * @return HasMany<DocumentoPersona, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPersona::class);
    }

    /**
     * Cuenta de acceso, si la persona tiene una (solo mayores de edad).
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Matrículas (vínculos con grupos) de la persona a lo largo del tiempo.
     *
     * @return HasMany<Matricula, $this>
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /**
     * Faceta de instructor (rango, supervisor, certificación), si la tiene.
     *
     * @return HasOne<Instructor, $this>
     */
    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    /**
     * Créditos de graduación acumulados por la persona (origen + cadena). El total
     * sostiene el avance del distintivo de collar (ver ServicioCreditos).
     *
     * @return HasMany<CreditoGraduacion, $this>
     */
    public function creditosGraduacion(): HasMany
    {
        return $this->hasMany(CreditoGraduacion::class);
    }

    /**
     * Matrícula activa de la persona (solo puede haber una en la federación).
     *
     * @return HasOne<Matricula, $this>
     */
    public function matriculaActiva(): HasOne
    {
        return $this->hasOne(Matricula::class)->where('estado', EstadoMatricula::Activa->value);
    }
}
