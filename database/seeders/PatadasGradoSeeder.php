<?php

namespace Database\Seeders;

use App\Enums\CategoriaTecnica;
use App\Enums\NivelEntrenamiento;
use App\Models\Grado;
use App\Models\Tecnica;
use Illuminate\Database\Seeder;

/**
 * Patadas por grado, en DOS fuentes para la comparativa de currículo:
 *
 *  - 'bekho': lo que se enseña y se rinde en examen en BEKHO Chile.
 *  - 'ata':   la referencia oficial del Manual ATA Legacy ("Patadas del plan de
 *             estudios").
 *
 * Cada patada se crea como técnica (categoría "patada") con su fuente y se
 * enlaza al grado de su color en todas las escalas (grado_tecnica). Reemplaza la
 * técnica-resumen "Patadas de cinturón X". Debe correr DESPUÉS de
 * TecnicasSeeder/GradoTecnicaSeeder. Idempotente.
 */
class PatadasGradoSeeder extends Seeder
{
    /** Cinturones de color cuyos resúmenes de patadas se reemplazan. */
    private const RESUMENES = [
        'Patadas de cinturón Blanco', 'Patadas de cinturón Naranjo',
        'Patadas de cinturón Amarillo', 'Patadas de cinturón Camuflaje',
        'Patadas de cinturón Verde', 'Patadas de cinturón Morado',
        'Patadas de cinturón Azul', 'Patadas de cinturón Marrón',
        'Patadas de cinturón Rojo',
    ];

    public function run(): void
    {
        // Quita las técnicas-resumen de patadas por cinturón (y sus enlaces).
        Tecnica::where('categoria', CategoriaTecnica::Patada->value)
            ->whereIn('nombre', self::RESUMENES)
            ->delete();

        $this->sembrar('bekho', $this->bekho());
        $this->sembrar('ata', $this->ata());
    }

    /**
     * @param  array<string, array{nivel: NivelEntrenamiento, kicks: list<array{0: string, 1: ?string}>}>  $data
     */
    private function sembrar(string $fuente, array $data): void
    {
        foreach ($data as $color => $datos) {
            $gradoIds = Grado::where('color', $color)->pluck('id')->all();

            foreach ($datos['kicks'] as $orden => $kick) {
                // [nombre, variantes, descripción propia opcional].
                [$nombre, $variantes, $descripcion] = array_pad($kick, 3, null);

                $tecnica = Tecnica::updateOrCreate(
                    [
                        'categoria' => CategoriaTecnica::Patada->value,
                        'fuente' => $fuente,
                        'nombre' => $nombre,
                        'cinturon' => $color,
                    ],
                    [
                        'descripcion' => $descripcion ?? ($variantes ? "Ejecuciones: {$variantes}." : null),
                        'nivel' => $datos['nivel']->value,
                        'core' => true,
                        'orden' => $orden + 1,
                    ],
                );

                $tecnica->grados()->sync($gradoIds);
            }
        }
    }

    /**
     * Currículo BEKHO (examen). Grados 5→1 pendientes de aportar.
     *
     * @return array<string, array{nivel: NivelEntrenamiento, kicks: list<array{0: string, 1: ?string}>}>
     */
    private function bekho(): array
    {
        $p = NivelEntrenamiento::Principiantes;
        $i = NivelEntrenamiento::Intermedio;

        return [
            'Blanco' => ['nivel' => $p, 'kicks' => [
                ['Patada de Frente', 'N1, N2, N3, N4'],
                ['Patada de Costado', '1, 2, 3, 4'],
                ['Levantamiento de pierna recto', null],
            ]],
            'Naranjo' => ['nivel' => $p, 'kicks' => [
                ['Patada de Vuelta', '1, 2, 3, 4'],
            ]],
            'Amarillo' => ['nivel' => $p, 'kicks' => [
                ['Patada Circular (dentro/afuera)', '1, 2, 3, 4'],
                ['Patada de Frente saltando', '1, 2, 3, 4'],
            ]],
            'Camuflado' => ['nivel' => $i, 'kicks' => [
                ['Giro de costado', 'A, B, C, D', 'Variantes: A = giro por la espalda cayendo adelante · '
                    .'B = con paso, giro por la espalda cayendo adelante · '
                    .'C = giro por la espalda cayendo atrás · '
                    .'D = con paso, giro por la espalda cayendo atrás.'],
            ]],
        ];
    }

    /**
     * Currículo ATA de referencia (Manual Legacy, "Patadas del plan de estudios").
     *
     * @return array<string, array{nivel: NivelEntrenamiento, kicks: list<array{0: string, 1: ?string}>}>
     */
    private function ata(): array
    {
        $p = NivelEntrenamiento::Principiantes;
        $i = NivelEntrenamiento::Intermedio;
        $a = NivelEntrenamiento::Avanzado;

        return [
            'Blanco' => ['nivel' => $p, 'kicks' => [
                ['Patada lateral', 'n.º 1, n.º 2 y n.º 3'],
            ]],
            'Naranjo' => ['nivel' => $p, 'kicks' => [
                ['Patada circular', 'n.º 1, n.º 2 y n.º 3'],
            ]],
            'Amarillo' => ['nivel' => $p, 'kicks' => [
                ['Patada frontal', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada frontal en salto', 'n.º 1, n.º 2 y n.º 3'],
            ]],
            'Camuflado' => ['nivel' => $i, 'kicks' => [
                ['Patada lateral invertida', null],
                ['Patada lateral invertida con paso', null],
            ]],
            'Verde' => ['nivel' => $i, 'kicks' => [
                ['Patada lateral', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada lateral en salto', 'n.º 1, n.º 2 y n.º 3'],
            ]],
            'Púrpura' => ['nivel' => $i, 'kicks' => [
                ['Patada creciente interna', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada creciente externa', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada creciente externa con giro', null],
                ['Patada creciente externa con giro y paso', null],
                ['Patada mariposa', null],
            ]],
            'Azul' => ['nivel' => $a, 'kicks' => [
                ['Patada de talón con giro', null],
                ['Patada de talón con giro y paso', null],
                ['Patada de hacha', 'n.º 1, n.º 2 y n.º 3'],
            ]],
            'Café' => ['nivel' => $a, 'kicks' => [
                ['Patada creciente externa en salto', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada creciente externa con giro en salto', null],
                ['Patada creciente externa con giro en salto y paso', null],
                ['Patada lateral invertida en salto', null],
                ['Patada lateral invertida en salto y paso', null],
            ]],
            'Rojo' => ['nivel' => $a, 'kicks' => [
                ['Patada de gancho en salto', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada circular en salto', 'n.º 1, n.º 2 y n.º 3'],
                ['Patada de gancho con giro en salto', null],
                ['Patada de gancho con giro en salto y paso', null],
            ]],
        ];
    }
}
