<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\User;

/**
 * Autorización sobre las fichas de estudiantes.
 *
 * - Quien tiene "gestionar alumnos" administra todas las fichas (de su academia).
 * - Un apoderado solo puede VER a sus propios hijos (no administrarlos).
 */
class EstudiantePolicy
{
    /**
     * Ve el listado de estudiantes: gestores o apoderados (estos verán solo los
     * suyos, filtrados por la consulta del componente).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('gestionar alumnos') || $user->hasRole('apoderado');
    }

    /**
     * Ve una ficha concreta: gestores, o el apoderado del estudiante.
     */
    public function view(User $user, Estudiante $estudiante): bool
    {
        if ($user->can('gestionar alumnos')) {
            return true;
        }

        return $estudiante->apoderados()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar alumnos');
    }

    public function update(User $user, Estudiante $estudiante): bool
    {
        return $user->can('gestionar alumnos');
    }

    public function delete(User $user, Estudiante $estudiante): bool
    {
        return $user->can('gestionar alumnos');
    }
}
