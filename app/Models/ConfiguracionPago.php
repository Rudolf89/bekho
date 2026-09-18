<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de pagos por grupo (cada escuela tiene sus valores).
 */
class ConfiguracionPago extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'configuraciones_pago';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'valor_mensualidad',
        'valor_matricula',
        'dia_vencimiento',
        'descuento_hermanos_pct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor_mensualidad' => 'integer',
            'valor_matricula' => 'integer',
            'dia_vencimiento' => 'integer',
            'descuento_hermanos_pct' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
