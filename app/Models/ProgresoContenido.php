<?php

namespace App\Models;

use App\Enums\EstadoProgreso;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresoContenido extends Model
{
    use PerteneceAcademia;

    /**
     * Tabla asociada.
     *
     * @var string
     */
    protected $table = 'progreso_contenidos';

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'user_id',
        'contenido_id',
        'estado',
        'visto_en',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoProgreso::class,
            'visto_en' => 'datetime',
        ];
    }

    /**
     * Usuario dueño del progreso.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contenido al que corresponde el progreso.
     *
     * @return BelongsTo<Contenido, $this>
     */
    public function contenido(): BelongsTo
    {
        return $this->belongsTo(Contenido::class);
    }
}
