<?php

namespace App\Models;

use App\Enums\TipoBeca;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Beca o convenio aplicado a una matrícula (descuento en % o monto fijo).
 */
class Beca extends Model
{
    use PerteneceGrupo;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'matricula_id',
        'tipo',
        'valor',
        'motivo',
        'vigente_desde',
        'vigente_hasta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoBeca::class,
            'valor' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    /**
     * ¿La beca está vigente a la fecha dada?
     */
    public function estaVigente(?\DateTimeInterface $a = null): bool
    {
        $a = $a ?? now();

        return ($this->vigente_desde === null || $this->vigente_desde <= $a)
            && ($this->vigente_hasta === null || $this->vigente_hasta >= $a);
    }

    /**
     * Aplica la beca a un monto y devuelve el resultado (nunca negativo).
     */
    public function aplicar(int $monto): int
    {
        $rebajado = $this->tipo === TipoBeca::Porcentaje
            ? (int) round($monto * (100 - $this->valor) / 100)
            : $monto - $this->valor;

        return max(0, $rebajado);
    }

    /**
     * @return BelongsTo<Matricula, $this>
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }
}
