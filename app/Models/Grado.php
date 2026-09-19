<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use App\Enums\NivelEntrenamiento;
use App\Enums\TipoGrado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catálogo compartido de grados (cinturones). NO usa el trait PerteneceGrupo:
 * es transversal a todos los grupos (como cargos_rangos).
 *
 * Soporta múltiples escalas de cinturones: Tigers usa un sistema propio de
 * rangos y parches, distinto al del resto de los grupos. Ver App\Enums\EscalaGrado.
 */
class Grado extends Model
{
    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'nombre',
        'orden',
        'escala',
        'tramo_id',
        'color',
        'tipo',
        'franjas',
        'estrellas',
        'meses_sugeridos',
        'requiere_nominacion',
        'significado',
        'activo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'escala' => EscalaGrado::class,
            'tipo' => TipoGrado::class,
            'franjas' => 'integer',
            'estrellas' => 'integer',
            'meses_sugeridos' => 'integer',
            'requiere_nominacion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Federación dueña del catálogo.
     *
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * Tramo de entrenamiento al que pertenece el grado.
     *
     * @return BelongsTo<TramoEntrenamiento, $this>
     */
    public function tramo(): BelongsTo
    {
        return $this->belongsTo(TramoEntrenamiento::class, 'tramo_id');
    }

    /**
     * Técnicas del currículo que corresponden a este cinturón.
     *
     * @return BelongsToMany<Tecnica, $this>
     */
    public function tecnicas(): BelongsToMany
    {
        return $this->belongsToMany(Tecnica::class, 'grado_tecnica');
    }

    /**
     * ¿Es el cinturón "recomendado" del par recomendado/decidido (For Kids)?
     */
    public function esRecomendado(): bool
    {
        return $this->tipo === TipoGrado::Recomendado;
    }

    /**
     * ¿Es el cinturón "decidido" del par recomendado/decidido (For Kids)?
     */
    public function esDecidido(): bool
    {
        return $this->tipo === TipoGrado::Decidido;
    }

    /**
     * Nivel de entrenamiento (Principiantes/Intermedio/Avanzado) al que
     * corresponde este cinturón, según su color. El nivel del alumno se deriva
     * de aquí: nuevo/sin cinturón (Blanco) => Principiantes.
     *
     * Corte por color: Blanco–Amarillo = Principiantes; Camuflado–Púrpura =
     * Intermedio; Azul–Rojo = Avanzado; el Rojo/Negro y los danes (Negro) son
     * sus propias categorías.
     */
    public function nivelEntrenamiento(): NivelEntrenamiento
    {
        return match ($this->color) {
            'Camuflado', 'Verde', 'Púrpura' => NivelEntrenamiento::Intermedio,
            'Azul', 'Café', 'Rojo' => NivelEntrenamiento::Avanzado,
            'Rojo/Negro' => NivelEntrenamiento::RojoNegro,
            'Negro' => NivelEntrenamiento::Danes,
            default => NivelEntrenamiento::Principiantes,
        };
    }

    /**
     * Filtra por escala de grados.
     *
     * @param  Builder<Grado>  $query
     * @return Builder<Grado>
     */
    public function scopePorEscala(Builder $query, EscalaGrado $escala): Builder
    {
        return $query->where('escala', $escala->value);
    }

    /**
     * Ordenados por el campo orden.
     *
     * @param  Builder<Grado>  $query
     * @return Builder<Grado>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
