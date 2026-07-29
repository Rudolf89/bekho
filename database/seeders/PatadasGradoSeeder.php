<?php

namespace Database\Seeders;

use App\Enums\CategoriaTecnica;
use App\Enums\NivelEntrenamiento;
use App\Models\Grado;
use App\Models\Tecnica;
use Illuminate\Database\Seeder;

/**
 * Patadas por grado (currículo BEKHO Chile). Reemplaza la técnica-resumen
 * "Patadas de cinturón X" por las patadas detalladas de cada grado, enlazadas al
 * cinturón vía grado_tecnica. Catálogo compartido.
 *
 * Debe correr DESPUÉS de TecnicasSeeder/GradoTecnicaSeeder. Idempotente.
 * Sólo cubre los grados provistos (9→6); el resto conserva su resumen hasta que
 * se aporten las patadas.
 */
class PatadasGradoSeeder extends Seeder
{
    /**
     * color de grado => [nivel, resumen a reemplazar, [ [patada, variantes], … ] ].
     */
    private function patadas(): array
    {
        return [
            'Blanco' => [
                'nivel' => NivelEntrenamiento::Principiantes,
                'resumen' => 'Patadas de cinturón Blanco',
                'kicks' => [
                    ['Patada de Frente', 'N1, N2, N3, N4'],
                    ['Patada de Costado', '1, 2, 3, 4'],
                    ['Levantamiento de pierna recto', null],
                ],
            ],
            'Naranjo' => [
                'nivel' => NivelEntrenamiento::Principiantes,
                'resumen' => 'Patadas de cinturón Naranjo',
                'kicks' => [
                    ['Patada de Vuelta', '1, 2, 3, 4'],
                ],
            ],
            'Amarillo' => [
                'nivel' => NivelEntrenamiento::Principiantes,
                'resumen' => 'Patadas de cinturón Amarillo',
                'kicks' => [
                    ['Patada Circular (dentro/afuera)', '1, 2, 3, 4'],
                    ['Patada de Frente saltando', '1, 2, 3, 4'],
                ],
            ],
            'Camuflado' => [
                'nivel' => NivelEntrenamiento::Intermedio,
                'resumen' => 'Patadas de cinturón Camuflaje',
                'kicks' => [
                    ['Giro de costado', 'A, B, C, D'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->patadas() as $color => $datos) {
            // Quita la técnica-resumen del cinturón (y en cascada sus enlaces).
            Tecnica::where('categoria', CategoriaTecnica::Patada->value)
                ->where('nombre', $datos['resumen'])
                ->delete();

            $gradoIds = Grado::where('color', $color)->pluck('id')->all();

            foreach ($datos['kicks'] as $orden => [$nombre, $variantes]) {
                $tecnica = Tecnica::updateOrCreate(
                    ['categoria' => CategoriaTecnica::Patada->value, 'nombre' => $nombre, 'cinturon' => $color],
                    [
                        'descripcion' => $variantes ? "Ejecuciones: {$variantes}." : null,
                        'nivel' => $datos['nivel']->value,
                        'core' => true,
                        'orden' => $orden + 1,
                    ],
                );

                $tecnica->grados()->sync($gradoIds);
            }
        }
    }
}
