<?php

namespace App\Services;

use App\Enums\EstadoProgreso;
use App\Models\Contenido;
use App\Models\Nivel;
use App\Models\ProgresoContenido;
use App\Models\User;

/**
 * Lógica de dominio del módulo de Formación (LMS).
 */
class ServicioFormacion
{
    /**
     * Marca el estado de progreso de un usuario sobre un contenido.
     *
     * Usa updateOrCreate sobre (user_id, contenido_id) para garantizar un único
     * registro por par. Registra visto_en cuando el estado deja de ser Pendiente.
     */
    public function marcarContenido(User $user, Contenido $contenido, EstadoProgreso $estado): ProgresoContenido
    {
        return ProgresoContenido::updateOrCreate(
            [
                'user_id' => $user->id,
                'contenido_id' => $contenido->id,
            ],
            [
                // El progreso pertenece a la academia del contenido. Se fija de
                // forma explícita para que también funcione cuando no hay tenant
                // activo (p. ej. un super-admin, sin academia, revisando).
                'academia_id' => $contenido->academia_id,
                'estado' => $estado,
                'visto_en' => $estado === EstadoProgreso::Pendiente ? null : now(),
            ],
        );
    }

    /**
     * Calcula el avance del usuario en un nivel.
     *
     * @return array{total: int, completados: int, porcentaje: int}
     */
    public function avanceDeNivel(User $user, Nivel $nivel): array
    {
        $total = $nivel->contenidos()->where('activo', true)->count();

        $completados = $nivel->contenidos()
            ->where('activo', true)
            ->whereHas('progresos', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('estado', EstadoProgreso::Completado->value);
            })
            ->count();

        $porcentaje = $total > 0 ? (int) round($completados / $total * 100) : 0;

        return [
            'total' => $total,
            'completados' => $completados,
            'porcentaje' => $porcentaje,
        ];
    }
}
