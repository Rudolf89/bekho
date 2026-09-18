<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\User;

/**
 * Autorización sobre las fichas de estudiantes.
 *
 * - Quien tiene "gestionar alumnos" administra todas las fichas (de su grupo).
 * - Un instructor ve y edita solo los alumnos de las clases donde está asignado.
 * - Un apoderado solo puede VER a sus propios hijos (no administrarlos).
 */
class EstudiantePolicy
{
    /**
     * Ve el listado de estudiantes: gestores, instructores o apoderados. Cada
     * uno verá solo lo que le corresponde (Estudiante::scopeVisiblePara).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('gestionar alumnos')
            || $user->hasRole('instructor')
            || $user->hasRole('apoderado');
    }

    /**
     * Ve una ficha concreta: gestores, el instructor de sus clases, o el
     * apoderado del estudiante.
     */
    public function view(User $user, Estudiante $estudiante): bool
    {
        if ($user->can('gestionar alumnos')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $estudiante->esDeInstructor($user);
        }

        return $estudiante->apoderados()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        // La federación es solo lectura: ve alumnos de todos los grupos pero
        // no los administra.
        return ! $user->esSoloLectura() && $user->can('gestionar alumnos');
    }

    /**
     * Edita una ficha: gestores, o el instructor de las clases del alumno.
     */
    public function update(User $user, Estudiante $estudiante): bool
    {
        if ($user->esSoloLectura()) {
            return false;
        }

        if ($user->can('gestionar alumnos')) {
            return true;
        }

        return $user->hasRole('instructor') && $estudiante->esDeInstructor($user);
    }

    public function delete(User $user, Estudiante $estudiante): bool
    {
        return ! $user->esSoloLectura() && $user->can('gestionar alumnos');
    }
}
