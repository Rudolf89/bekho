<?php

namespace App\Models;

use App\Enums\TipoBloque;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloquePlanilla extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'bloques_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'planilla_id',
        'tipo',
        'contenido',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoBloque::class,
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
