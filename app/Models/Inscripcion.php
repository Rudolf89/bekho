<?php

namespace App\Models;

use App\Enums\ResultadoExamen;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscripcion extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'inscripciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'convocatoria_id',
        'estudiante_id',
        'grado_origen_id',
        'grado_destino_id',
        'instructor_id',
        'visto_bueno',
        'resultado',
        'nota',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visto_bueno' => 'boolean',
            'resultado' => ResultadoExamen::class,
            'nota' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Convocatoria, $this>
     */
    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
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
    public function gradoOrigen(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_origen_id');
    }

    /**
     * @return BelongsTo<Grado, $this>
     */
    public function gradoDestino(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_destino_id');
    }

    /**
     * Instructor a quien se acredita la graduación (conteo en cascada).
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
