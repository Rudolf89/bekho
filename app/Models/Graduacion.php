<?php

namespace App\Models;

use App\Enums\ResultadoExamen;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de una graduación aprobada. Base del conteo en cascada por
 * instructor (instructor_id) a través de la línea de supervisión.
 */
class Graduacion extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'graduaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'estudiante_id',
        'convocatoria_id',
        'grado_origen_id',
        'grado_destino_id',
        'instructor_id',
        'fecha',
        'resultado',
        'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'resultado' => ResultadoExamen::class,
            'nota' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Estudiante, $this>
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * @return BelongsTo<Grado, $this>
     */
    public function gradoDestino(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_destino_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
