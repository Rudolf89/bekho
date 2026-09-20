<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento de identidad de una persona (RUT, pasaporte).
 */
class DocumentoPersona extends Model
{
    /**
     * @var string
     */
    protected $table = 'documentos_persona';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'tipo',
        'numero',
        'pais',
        'principal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoDocumento::class,
            'principal' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
