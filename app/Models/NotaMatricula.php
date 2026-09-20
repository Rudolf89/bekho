<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota del instructor sobre una matrícula (alumno). Dato operativo del grupo.
 */
class NotaMatricula extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'notas_matricula';

    /**
     * @var list<string>
     */
    protected $fillable = ['grupo_id', 'matricula_id', 'autor_user_id', 'cuerpo'];

    /**
     * @return BelongsTo<Matricula, $this>
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_user_id');
    }
}
