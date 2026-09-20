<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asistencia de un trainee con papel de AYUDANTE a una clase en una fecha. Dato
 * operativo (con grupo_id). Las horas quedan congeladas al registrarse.
 */
class AsistenciaAyudante extends Model
{
    use PerteneceGrupo;

    protected $table = 'asistencias_ayudante';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id', 'persona_id', 'clase_id', 'fecha', 'horas', 'registrado_por_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'horas' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Clase, $this>
     */
    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
