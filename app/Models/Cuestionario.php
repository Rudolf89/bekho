<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cuestionario (evaluación autocorregida). Catálogo compartido (sin grupo_id):
 * un examinador lo arma con preguntas y opciones, y cualquiera con permiso lo
 * rinde. El primer banco es el examen de juez ATA, pero el módulo es genérico.
 */
class Cuestionario extends Model
{
    protected $table = 'cuestionarios';

    /**
     * @var list<string>
     */
    protected $fillable = ['titulo', 'descripcion', 'area', 'umbral_aprobacion', 'activo', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'umbral_aprobacion' => 'integer',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * @return HasMany<PreguntaCuestionario, $this>
     */
    public function preguntas(): HasMany
    {
        return $this->hasMany(PreguntaCuestionario::class)->orderBy('orden');
    }

    /**
     * @return HasMany<IntentoCuestionario, $this>
     */
    public function intentos(): HasMany
    {
        return $this->hasMany(IntentoCuestionario::class);
    }

    /**
     * @param  Builder<Cuestionario>  $query
     * @return Builder<Cuestionario>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @param  Builder<Cuestionario>  $query
     * @return Builder<Cuestionario>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('titulo');
    }
}
