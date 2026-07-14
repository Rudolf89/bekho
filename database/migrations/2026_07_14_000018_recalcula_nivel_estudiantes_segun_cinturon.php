<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El nivel del alumno pasa a derivarse de su cinturón (grado). Esta migración
 * recalcula el nivel de los alumnos existentes según el color de su cinturón:
 *
 *   Blanco / Naranjo / Amarillo         -> Principiantes
 *   Camuflado / Verde / Púrpura         -> Intermedio
 *   Azul / Café / Rojo / Rojo/Negro / Negro (danes) -> Avanzado
 *
 * Sin cinturón (alumno nuevo) => Principiantes. En una base recién migrada el
 * catálogo de grados aún no está sembrado, así que estos UPDATE no afectan a
 * nadie y los alumnos de la semilla toman su nivel al crearse.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bandas = [
            'principiantes' => ['Blanco', 'Naranjo', 'Amarillo'],
            'intermedio' => ['Camuflado', 'Verde', 'Púrpura'],
            'avanzado' => ['Azul', 'Café', 'Rojo', 'Rojo/Negro', 'Negro'],
        ];

        foreach ($bandas as $nivel => $colores) {
            $gradoIds = DB::table('grados')->whereIn('color', $colores)->pluck('id');

            if ($gradoIds->isNotEmpty()) {
                DB::table('estudiantes')->whereIn('grado_id', $gradoIds)->update(['nivel' => $nivel]);
            }
        }

        // Alumnos sin cinturón: principiantes.
        DB::table('estudiantes')->whereNull('grado_id')->update(['nivel' => 'principiantes']);
    }

    public function down(): void
    {
        // Sin reversa: el nivel es un valor derivado, no hay estado anterior que restaurar.
    }
};
