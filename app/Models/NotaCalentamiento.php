<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use Illuminate\Database\Eloquent\Model;

/**
 * Nota de calentamiento por grupo etario (ajustes específicos). Catálogo
 * compartido (sin grupo_id).
 */
class NotaCalentamiento extends Model
{
    protected $table = 'notas_calentamiento';

    /**
     * @var list<string>
     */
    protected $fillable = ['grupo_etario', 'nota'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['grupo_etario' => GrupoEtario::class];
    }
}
