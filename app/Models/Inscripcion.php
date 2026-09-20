<?php

namespace App\Models;

use App\Enums\ResultadoExamen;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscripcion extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'inscripciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'convocatoria_id',
        'matricula_id',
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
     * @return BelongsTo<Matricula, $this>
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
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
     * Examinador de la inscripción (la cuenta que examina; cualquier instructor
     * puede examinar, incluso de otro grupo). NO determina el crédito: el crédito
     * lo recibe el instructor acreditado, que se resuelve por las reglas de origen
     * al graduar (ver App\Services\ServicioCreditos). De aquí sale, por persona,
     * graduaciones.examinador_persona_id.
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
