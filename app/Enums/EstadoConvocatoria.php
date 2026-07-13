<?php

namespace App\Enums;

/**
 * Estado de una convocatoria de examen.
 *
 * - Programada: admite inscripciones.
 * - Cerrada: no admite más inscripciones; pendiente de registrar resultados.
 * - Finalizada: resultados registrados y graduaciones aplicadas.
 */
enum EstadoConvocatoria: string
{
    case Programada = 'programada';
    case Cerrada = 'cerrada';
    case Finalizada = 'finalizada';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Cerrada => 'Cerrada',
            self::Finalizada => 'Finalizada',
        };
    }
}
