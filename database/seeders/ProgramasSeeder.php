<?php

namespace Database\Seeders;

use App\Models\Programa;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo compartido de programas ATA (los seis oficiales).
 *
 * Recordatorio: los programas de formación "Leadership" y "Legacy" NO son los
 * rangos homónimos de cargos_rangos; son la ruta que se cursa, no la meta que
 * se alcanza. Ver el comentario en App\Models\Programa.
 */
class ProgramasSeeder extends Seeder
{
    public function run(): void
    {
        $programas = [
            ['nombre' => 'Xtreme', 'tipo' => 'disciplina', 'edad_minima' => 5, 'descripcion' => 'Disciplina paralela: acrobacias y armas.'],
            ['nombre' => 'Defense', 'tipo' => 'disciplina', 'edad_minima' => null, 'descripcion' => 'Disciplina paralela: defensa personal.'],
            ['nombre' => 'Black Belt Club', 'tipo' => 'progresion', 'edad_minima' => null, 'descripcion' => 'Progresión hacia el cinturón negro.'],
            ['nombre' => 'Master Club', 'tipo' => 'progresion', 'edad_minima' => null, 'descripcion' => 'Progresión hacia maestro.'],
            ['nombre' => 'Leadership', 'tipo' => 'formacion', 'edad_minima' => null, 'descripcion' => 'Formación de instructores.'],
            ['nombre' => 'Legacy', 'tipo' => 'formacion', 'edad_minima' => null, 'descripcion' => 'Formación de instructores.'],
        ];

        foreach ($programas as $orden => $programa) {
            Programa::updateOrCreate(
                ['nombre' => $programa['nombre']],
                [
                    'tipo' => $programa['tipo'],
                    'edad_minima' => $programa['edad_minima'],
                    'descripcion' => $programa['descripcion'],
                    'orden' => $orden,
                    'activo' => true,
                ],
            );
        }
    }
}
