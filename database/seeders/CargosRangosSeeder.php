<?php

namespace Database\Seeders;

use App\Models\CargoRango;
use Illuminate\Database\Seeder;

class CargosRangosSeeder extends Seeder
{
    /**
     * Siembra el catálogo compartido de cargos/rangos (nivel 1 = más alto).
     */
    public function run(): void
    {
        $rangos = [
            ['nombre' => 'Gran Maestro', 'nivel' => 1, 'grado_dan' => 9, 'grado_dan_max' => null, 'uniforme_gala' => 'Negro/Oro', 'collar' => null],
            ['nombre' => 'Maestro Jefe', 'nivel' => 2, 'grado_dan' => 8, 'grado_dan_max' => null, 'uniforme_gala' => 'Rojo', 'collar' => null],
            ['nombre' => 'Maestro Sénior', 'nivel' => 3, 'grado_dan' => 7, 'grado_dan_max' => null, 'uniforme_gala' => 'Azul', 'collar' => null],
            ['nombre' => 'Maestro', 'nivel' => 4, 'grado_dan' => 6, 'grado_dan_max' => null, 'uniforme_gala' => 'Blanco', 'collar' => null],
            // El Profesor abarca de 2º a 5º Dan.
            ['nombre' => 'Profesor', 'nivel' => 5, 'grado_dan' => 2, 'grado_dan_max' => 5, 'uniforme_gala' => null, 'collar' => 'Negro sólido'],
            // Para ser Instructor se exige el 1.er Dan (cinturón negro).
            ['nombre' => 'Instructor', 'nivel' => 6, 'grado_dan' => 1, 'grado_dan_max' => null, 'uniforme_gala' => null, 'collar' => 'Negro/Rojo/Negro'],
            ['nombre' => 'Legado (Ayudante)', 'nivel' => 7, 'grado_dan' => null, 'grado_dan_max' => null, 'uniforme_gala' => null, 'collar' => 'Rojo'],
        ];

        foreach ($rangos as $rango) {
            CargoRango::updateOrCreate(
                ['nombre' => $rango['nombre']],
                $rango,
            );
        }
    }
}
