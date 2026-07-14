<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los niveles pasan de 3 a 5 categorías: se separan Rojo/Negro y los cinturones
 * negros (danes) de "Avanzado". Esta migración recalcula el nivel de los alumnos
 * existentes según el color de su cinturón:
 *
 *   Blanco / Naranjo / Amarillo   -> Principiantes
 *   Camuflado / Verde / Púrpura   -> Intermedio
 *   Azul / Café / Rojo            -> Avanzado
 *   Rojo/Negro                    -> Rojo/Negro
 *   Negro (1º–9º Dan)             -> Danes
 *
 * Sin cinturón => Principiantes. En una base recién migrada el catálogo de
 * grados aún no existe, así que estos UPDATE no afectan a nadie.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bandas = [
            'principiantes' => ['Blanco', 'Naranjo', 'Amarillo'],
            'intermedio' => ['Camuflado', 'Verde', 'Púrpura'],
            'avanzado' => ['Azul', 'Café', 'Rojo'],
            'rojo_negro' => ['Rojo/Negro'],
            'danes' => ['Negro'],
        ];

        foreach ($bandas as $nivel => $colores) {
            $gradoIds = DB::table('grados')->whereIn('color', $colores)->pluck('id');

            if ($gradoIds->isNotEmpty()) {
                DB::table('estudiantes')->whereIn('grado_id', $gradoIds)->update(['nivel' => $nivel]);
            }
        }

        DB::table('estudiantes')->whereNull('grado_id')->update(['nivel' => 'principiantes']);
    }

    public function down(): void
    {
        // Sin reversa: el nivel es un valor derivado.
    }
};
