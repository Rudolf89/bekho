<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de pagos por academia (cada escuela tiene sus valores).
 */
class ConfiguracionPago extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'configuraciones_pago';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
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
     * @return BelongsTo<Academia, $this>
     */
    public function academia(): BelongsTo
    {
        return $this->belongsTo(Academia::class);
    }
}
