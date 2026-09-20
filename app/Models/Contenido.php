<?php

namespace App\Models;

use App\Enums\TipoContenido;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contenido extends Model
{
    use PerteneceGrupo;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'nivel_id',
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
     * Casts de atributos.
     *
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
     * Nivel al que pertenece el contenido.
     *
     * @return BelongsTo<Nivel, $this>
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }

    /**
     * Etapa de programa a la que pertenece el contenido (modelo unificado).
     *
     * @return BelongsTo<EtapaPrograma, $this>
     */
    public function etapaPrograma(): BelongsTo
    {
        return $this->belongsTo(EtapaPrograma::class);
    }

    /**
     * Registros de progreso de los usuarios sobre este contenido.
     *
     * @return HasMany<ProgresoContenido, $this>
     */
    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoContenido::class);
    }
}
