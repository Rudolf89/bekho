<?php

namespace App\Models;

use App\Enums\Cuadrante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuadrante de enseñanza de una planilla. Transversal (contenido compartido).
 */
class CuadrantePlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'cuadrantes_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planilla_id',
        'cuadrante',
        'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuadrante' => Cuadrante::class,
        ];
    }

    /**
     * @return BelongsTo<Planilla, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }
}
