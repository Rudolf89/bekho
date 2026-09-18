<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horario (día + hora) de una clase recurrente. Una clase puede tener varios.
 */
class HorarioClase extends Model
{
    /**
     * @var string
     */
    protected $table = 'horarios_clase';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'clase_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
        ];
    }

    /**
     * @return BelongsTo<Clase, $this>
     */
    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }
}
