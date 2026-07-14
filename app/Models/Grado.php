<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use App\Enums\NivelEntrenamiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo compartido de grados (cinturones). NO usa el trait PerteneceAcademia:
 * es transversal a todas las academias (como cargos_rangos).
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
        'nombre',
        'orden',
        'escala',
        'color',
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
            'activo' => 'boolean',
        ];
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
