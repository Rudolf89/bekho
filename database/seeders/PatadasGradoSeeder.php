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

            // Grados 5→1 (Verde, Púrpura, Azul, Café, Rojo): tabla oficial del
            // Manual ATA Legacy ("Patadas del plan de estudios").
            'Verde' => [
                'nivel' => NivelEntrenamiento::Intermedio,
                'resumen' => 'Patadas de cinturón Verde',
                'kicks' => [
                    ['Patada lateral', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada lateral en salto', 'n.º 1, n.º 2 y n.º 3'],
                ],
            ],
            'Púrpura' => [
                'nivel' => NivelEntrenamiento::Intermedio,
                'resumen' => 'Patadas de cinturón Morado',
                'kicks' => [
                    ['Patada creciente interna', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada creciente externa', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada creciente externa con giro', null],
                    ['Patada creciente externa con giro y paso', null],
                    ['Patada mariposa', null],
                ],
            ],
            'Azul' => [
                'nivel' => NivelEntrenamiento::Avanzado,
                'resumen' => 'Patadas de cinturón Azul',
                'kicks' => [
                    ['Patada de talón con giro', null],
                    ['Patada de talón con giro y paso', null],
                    ['Patada de hacha', 'n.º 1, n.º 2 y n.º 3'],
                ],
            ],
            'Café' => [
                'nivel' => NivelEntrenamiento::Avanzado,
                'resumen' => 'Patadas de cinturón Marrón',
                'kicks' => [
                    ['Patada creciente externa en salto', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada creciente externa con giro en salto', null],
                    ['Patada creciente externa con giro en salto y paso', null],
                    ['Patada lateral invertida en salto', null],
                    ['Patada lateral invertida en salto y paso', null],
                ],
            ],
            'Rojo' => [
                'nivel' => NivelEntrenamiento::Avanzado,
                'resumen' => 'Patadas de cinturón Rojo',
                'kicks' => [
                    ['Patada de gancho en salto', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada circular en salto', 'n.º 1, n.º 2 y n.º 3'],
                    ['Patada de gancho con giro en salto', null],
                    ['Patada de gancho con giro en salto y paso', null],
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
