<?php

namespace App\Models;

use App\Enums\Cuadrante;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuadrantePlanilla extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'cuadrantes_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
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
