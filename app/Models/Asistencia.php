<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'asistencias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'clase_id',
        'estudiante_id',
        'registrado_por',
        'fecha',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoAsistencia::class,
        ];
    }

    /**
     * @return BelongsTo<Clase, $this>
     */
    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    /**
     * @return BelongsTo<Estudiante, $this>
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
