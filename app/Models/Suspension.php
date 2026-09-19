<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suspensión temporal (congelamiento) de una matrícula. Durante el período no se
 * genera cargo ni se marca morosidad.
 */
class Suspension extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'suspensiones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'matricula_id',
        'desde',
        'hasta',
        'motivo',
        'registrada_por_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'desde' => 'date',
            'hasta' => 'date',
        ];
    }

    /**
     * Acota a las suspensiones que solapan el mes del período dado (una
     * suspensión cubre el período si empieza antes del fin de mes y no terminó
     * antes de su inicio).
     *
     * @param  Builder<Suspension>  $query
     * @return Builder<Suspension>
     */
    public function scopeCubrePeriodo(Builder $query, CarbonInterface $periodo): Builder
    {
        $inicioMes = $periodo->copy()->startOfMonth();
        $finMes = $periodo->copy()->endOfMonth();

        return $query->whereDate('desde', '<=', $finMes)
            ->where(function (Builder $q) use ($inicioMes): void {
                $q->whereNull('hasta')->orWhereDate('hasta', '>=', $inicioMes);
            });
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
    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por_user_id');
    }
}
