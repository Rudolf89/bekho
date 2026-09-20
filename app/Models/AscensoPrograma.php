<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ascenso a una etapa de programa (historial). Lo aprueba el instructor del
 * alumno (matriculas.instructor_persona_id); la supervisión es solo informativa.
 */
class AscensoPrograma extends Model
{
    protected $table = 'ascensos_programa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inscripcion_programa_id', 'etapa_programa_id', 'fecha', 'aprobado_por_persona_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    /**
     * @return BelongsTo<InscripcionPrograma, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(InscripcionPrograma::class, 'inscripcion_programa_id');
    }

    /**
     * @return BelongsTo<EtapaPrograma, $this>
     */
    public function etapa(): BelongsTo
    {
        return $this->belongsTo(EtapaPrograma::class, 'etapa_programa_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'aprobado_por_persona_id');
    }
}
