<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra los roles al nuevo catálogo organizacional, conservando las
 * asignaciones de los usuarios (se renombra el registro, no se recrea):
 *
 *   super-admin -> admin-plataforma
 *   maestro     -> direccion
 *
 * Además elimina el rol 'ayudante': ser ayudante es un papel DENTRO de una clase
 * (pivote clase_instructor), no un rol de software. Sus usuarios pasan a
 * 'instructor'.
 *
 * Es idempotente y no pierde asignaciones aunque ambos nombres coexistan: en ese
 * caso consolida el rol viejo dentro del nuevo (reasigna usuarios y borra el
 * viejo). En una base recién migrada la tabla roles está vacía y no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->consolidaRol('super-admin', 'admin-plataforma');
        $this->consolidaRol('maestro', 'direccion');

        // 'ayudante' desaparece del catálogo: sus usuarios pasan a 'instructor'.
        $this->consolidaRol('ayudante', 'instructor');
    }

    public function down(): void
    {
        // Reversión de mejor esfuerzo de los renombres (no recrea 'ayudante').
        $this->consolidaRol('admin-plataforma', 'super-admin');
        $this->consolidaRol('direccion', 'maestro');
    }

    /**
     * Convierte el rol $viejo en $nuevo conservando las asignaciones:
     * - Si $viejo no existe: no hace nada.
     * - Si $nuevo no existe: renombra el registro (conserva asignaciones y permisos).
     * - Si ambos existen: reasigna los usuarios de $viejo a $nuevo (sin duplicar) y
     *   elimina $viejo con sus vínculos.
     */
    private function consolidaRol(string $viejo, string $nuevo): void
    {
        $rolViejo = DB::table('roles')->where('name', $viejo)->first();

        if (! $rolViejo) {
            return;
        }

        $rolNuevo = DB::table('roles')
            ->where('name', $nuevo)
            ->where('guard_name', $rolViejo->guard_name)
            ->first();

        if (! $rolNuevo) {
            DB::table('roles')->where('id', $rolViejo->id)->update(['name' => $nuevo]);

            return;
        }

        // Ambos existen: reasigna a los usuarios que aún no tengan el rol nuevo
        // (para no violar la llave compuesta de model_has_roles) y borra el viejo.
        $asignaciones = DB::table('model_has_roles')->where('role_id', $rolViejo->id)->get();

        foreach ($asignaciones as $fila) {
            $yaTieneNuevo = DB::table('model_has_roles')
                ->where('role_id', $rolNuevo->id)
                ->where('model_id', $fila->model_id)
                ->where('model_type', $fila->model_type)
                ->exists();

            if (! $yaTieneNuevo) {
                DB::table('model_has_roles')
                    ->where('role_id', $rolViejo->id)
                    ->where('model_id', $fila->model_id)
                    ->where('model_type', $fila->model_type)
                    ->update(['role_id' => $rolNuevo->id]);
            }
        }

        DB::table('model_has_roles')->where('role_id', $rolViejo->id)->delete();
        DB::table('role_has_permissions')->where('role_id', $rolViejo->id)->delete();
        DB::table('roles')->where('id', $rolViejo->id)->delete();
    }
};
