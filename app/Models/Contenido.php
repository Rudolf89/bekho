<?php

namespace App\Models;

use App\Enums\TipoContenido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contenido de estudio de una etapa de programa. Catálogo de la federación (sin
 * grupo_id): los manuales son de ATA, iguales para todos los grupos.
 */
class Contenido extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'etapa_programa_id',
        'titulo',
        'descripcion',
        'tipo',
        'cuerpo',
        'url_recurso',
        'orden',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoContenido::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Etapa de programa a la que pertenece el contenido.
     *
     * @return BelongsTo<EtapaPrograma, $this>
     */
    public function etapaPrograma(): BelongsTo
    {
        return $this->belongsTo(EtapaPrograma::class);
    }

    /**
     * Registros de progreso (por persona) sobre este contenido.
     *
     * @return HasMany<ProgresoContenido, $this>
     */
    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoContenido::class);
    }
}
