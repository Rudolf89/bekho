<?php

namespace App\Services;

use App\Enums\EstadoProgreso;
use App\Models\Contenido;
use App\Models\EtapaPrograma;
use App\Models\Persona;
use App\Models\ProgresoContenido;
use Illuminate\Support\Facades\Auth;

/**
 * Lógica de dominio de Programas (LMS + Legacy unificados). El progreso de
 * estudio cuelga de la PERSONA (los menores no tienen cuenta); el user que lo
 * registra queda como dato aparte.
 */
class ServicioProgramas
{
    /**
     * Marca el estado de progreso de una persona sobre un contenido (único por
     * persona+contenido). Registra visto_en cuando deja de estar pendiente.
     */
    public function marcarContenido(Persona $persona, Contenido $contenido, EstadoProgreso $estado): ProgresoContenido
    {
        return ProgresoContenido::updateOrCreate(
            ['persona_id' => $persona->id, 'contenido_id' => $contenido->id],
            [
                'registrado_por_user_id' => Auth::id(),
                'estado' => $estado,
                'visto_en' => $estado === EstadoProgreso::Pendiente ? null : now(),
            ],
        );
    }

    /**
     * Estado de progreso de una persona sobre un contenido.
     */
    public function progresoDe(Persona $persona, Contenido $contenido): EstadoProgreso
    {
        $estado = ProgresoContenido::where('persona_id', $persona->id)
            ->where('contenido_id', $contenido->id)
            ->value('estado');

        return $estado instanceof EstadoProgreso ? $estado : ($estado ? EstadoProgreso::from($estado) : EstadoProgreso::Pendiente);
    }

    /**
     * Avance de una persona en una etapa.
     *
     * @return array{total: int, completados: int, porcentaje: int}
     */
    public function avanceDeEtapa(Persona $persona, EtapaPrograma $etapa): array
    {
        $total = $etapa->contenidos()->where('activo', true)->count();

        $completados = $etapa->contenidos()
            ->where('activo', true)
            ->whereHas('progresos', fn ($q) => $q
                ->where('persona_id', $persona->id)
                ->where('estado', EstadoProgreso::Completado->value))
            ->count();

        return [
            'total' => $total,
            'completados' => $completados,
            'porcentaje' => $total > 0 ? (int) round($completados / $total * 100) : 0,
        ];
    }
}
