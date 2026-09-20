<?php

namespace App\Models;

use App\Enums\EstadoProgreso;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresoContenido extends Model
{
    use PerteneceGrupo;

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
        'grupo_id',
        'user_id',
        'persona_id',
        'registrado_por_user_id',
        'contenido_id',
        'estado',
        'visto_en',
    ];

    /**
     * El progreso cuelga de la PERSONA (los menores no tienen cuenta). Al crear,
     * se autocompleta persona_id desde el user y se guarda quién lo registró, sin
     * tocar los llamadores que aún trabajan por user_id (expand).
     */
    protected static function booted(): void
    {
        static::creating(function (ProgresoContenido $progreso): void {
            if ($progreso->persona_id === null && $progreso->user_id !== null) {
                $progreso->persona_id = User::whereKey($progreso->user_id)->value('persona_id');
            }

            if ($progreso->registrado_por_user_id === null) {
                $progreso->registrado_por_user_id = $progreso->user_id;
            }
        });
    }

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
