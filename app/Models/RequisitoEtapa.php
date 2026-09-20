<?php

namespace App\Models;

use App\Enums\TipoRequisitoEtapa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Requisito de avance de una etapa de programa (fusión de `requisitos_legacy`).
 * Catálogo de la federación. Un requisito de tipo cuestionario se cumple con un
 * intento aprobado del cuestionario enlazado; el resto se verifica a mano.
 */
class RequisitoEtapa extends Model
{
    protected $table = 'requisitos_etapa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'etapa_programa_id', 'descripcion', 'tipo', 'cantidad', 'cuestionario_id', 'orden', 'fuente', 'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoRequisitoEtapa::class,
            'cantidad' => 'integer',
            'orden' => 'integer',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EtapaPrograma, $this>
     */
    public function etapa(): BelongsTo
    {
        return $this->belongsTo(EtapaPrograma::class, 'etapa_programa_id');
    }

    /**
     * @return BelongsTo<Cuestionario, $this>
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    /**
     * ¿Es un requisito automático (prueba escrita)? Se cumple con un intento
     * aprobado del cuestionario enlazado, sin marca manual.
     */
    public function esAutomatico(): bool
    {
        return $this->tipo === TipoRequisitoEtapa::Cuestionario && $this->cuestionario_id !== null;
    }
}
