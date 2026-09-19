<?php

namespace App\Models;

use App\Enums\ResultadoBusqueda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría de una consulta de persona por documento entre grupos.
 * NO usa PerteneceGrupo: es una bitácora que cruza grupos y se consulta como
 * auditoría, no como dato operativo.
 */
class AccesoDato extends Model
{
    /**
     * @var string
     */
    protected $table = 'accesos_datos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'grupo_id',
        'documento_consultado',
        'resultado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['resultado' => ResultadoBusqueda::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
