<?php

namespace App\Models;

use App\Enums\TipoPago;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use PerteneceGrupo;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'matricula_id',
        'registrado_por',
        'tipo',
        'periodo',
        'monto',
        'fecha_pago',
        'medio',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPago::class,
            'periodo' => 'date',
            'fecha_pago' => 'date',
            'monto' => 'integer',
        ];
    }

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
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
