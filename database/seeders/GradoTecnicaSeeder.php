<?php

namespace Database\Seeders;

use App\Models\Grado;
use App\Models\Tecnica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Enlaza cada técnica de la biblioteca con el cinturón (grado) que le
 * corresponde. La biblioteca etiqueta la técnica con un color o dan; aquí se
 * resuelve a los grados de ese color en TODAS las escalas (una técnica de "Rojo"
 * corresponde al Rojo de Tigers, For Kids y Adultos).
 *
 * Debe correr DESPUÉS de GradosSeeder y TecnicasSeeder. Idempotente (sync).
 */
class GradoTecnicaSeeder extends Seeder
{
    /**
     * Grafías de la biblioteca que no coinciden con el color del catálogo de
     * grados.
     */
    private const MAPA_COLOR = [
        'Morado' => 'Púrpura',
        'Camuflaje' => 'Camuflado',
        'Marrón' => 'Café',
    ];

    public function run(): void
    {
        $tecnicas = Tecnica::query()
            ->whereNotNull('cinturon')
            ->where('cinturon', '!=', '')
            ->get();

        foreach ($tecnicas as $tecnica) {
            $gradoIds = $this->gradosPara($tecnica->cinturon);

            if ($gradoIds !== []) {
                $tecnica->grados()->sync($gradoIds);
            }
        }
    }

    /**
     * IDs de los grados que corresponden a la etiqueta de cinturón de una técnica.
     *
     * @return list<int>
     */
    private function gradosPara(string $cinturon): array
    {
        // Danes: "1.er Dan (decidido)", "2.º Dan (recomendado)"… → grado "Nº Dan".
        if (Str::contains($cinturon, 'Dan') && preg_match('/(\d+)/', $cinturon, $m)) {
            return Grado::where('nombre', "{$m[1]}º Dan")->pluck('id')->all();
        }

        // Cinturones de color: se normaliza la grafía y se toman todas las escalas.
        $color = self::MAPA_COLOR[$cinturon] ?? $cinturon;

        return Grado::where('color', $color)->pluck('id')->all();
    }
}
