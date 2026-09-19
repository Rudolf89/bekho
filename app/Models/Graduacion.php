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
        'matricula_id',
        'convocatoria_id',
        'grado_origen_id',
        'grado_destino_id',
        'instructor_id',
        'examinador_persona_id',
        'fecha',
        'fecha_entrega',
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
            'fecha_entrega' => 'date',
            'resultado' => ResultadoExamen::class,
            'nota' => 'decimal:1',
        ];
    }

    /**
     * ¿Falta entregar el cinturón (aprobada sin fecha de entrega)?
     */
    public function entregaPendiente(): bool
    {
        return $this->fecha_entrega === null;
    }

    /**
     * ¿Venció el plazo de 30 días para entregar el cinturón? Solo alerta: la
     * aprobación no caduca.
     */
    public function plazoEntregaVencido(?\DateTimeInterface $a = null): bool
    {
        return $this->entregaPendiente()
            && $this->fecha !== null
            && $this->fecha->copy()->addDays(30)->lt($a ?? now());
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
    public function gradoDestino(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_destino_id');
    }

    /**
     * Instructor acreditado (cuenta): sostiene el conteo en cascada.
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Examinador que evaluó la graduación (persona; puede ser de otro grupo).
     *
     * @return BelongsTo<Persona, $this>
     */
    public function examinador(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'examinador_persona_id');
    }
}
