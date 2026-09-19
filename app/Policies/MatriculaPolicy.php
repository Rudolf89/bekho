<?php

namespace App\Policies;

use App\Models\Matricula;
use App\Models\Tutela;
use App\Models\User;

/**
 * Autorización sobre las matrículas (alumnos).
 *
 * - Quien tiene "gestionar alumnos" administra todas las de su grupo.
 * - Un instructor ve y edita solo las de las clases donde está asignado.
 * - Un apoderado solo puede VER las de las personas que tutela.
 */
class MatriculaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gestionar alumnos')
            || $user->hasRole('instructor')
            || $user->hasRole('apoderado');
    }

    public function view(User $user, Matricula $matricula): bool
    {
        if ($user->can('gestionar alumnos')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $matricula->esDeInstructor($user);
        }

        return $user->persona_id !== null && Tutela::query()
            ->where('apoderado_persona_id', $user->persona_id)
            ->where('alumno_persona_id', $matricula->persona_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return ! $user->esSoloLectura() && $user->can('gestionar alumnos');
    }

    public function update(User $user, Matricula $matricula): bool
    {
        if ($user->esSoloLectura()) {
            return false;
        }

        if ($user->can('gestionar alumnos')) {
            return true;
        }

        return $user->hasRole('instructor') && $matricula->esDeInstructor($user);
    }

    public function delete(User $user, Matricula $matricula): bool
    {
        return ! $user->esSoloLectura() && $user->can('gestionar alumnos');
    }
}
