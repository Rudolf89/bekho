<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Requisito de un nivel Legacy (checklist). Si tiene cuestionario_id, se cumple
 * automáticamente cuando el usuario aprueba ese cuestionario (prueba escrita).
 * Catálogo compartido (cuelga del nivel).
 */
class RequisitoLegacy extends Model
{
    protected $table = 'requisitos_legacy';

    /**
     * @var list<string>
     */
    protected $fillable = ['nivel_legacy_id', 'texto', 'cuestionario_id', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer', 'verificado' => 'boolean'];
    }

    /**
     * @return BelongsTo<NivelLegacy, $this>
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(NivelLegacy::class, 'nivel_legacy_id');
    }

    /**
     * @return BelongsTo<Cuestionario, $this>
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    /**
     * ¿El requisito se verifica automáticamente con un cuestionario aprobado?
     */
    public function esAutomatico(): bool
    {
        return $this->cuestionario_id !== null;
    }
}
