<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Models\Grado;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo compartido de grados (cinturones) de las tres escalas.
 *
 * - Tigers: cada color liso y luego con su animal (18 grados).
 * - For Kids: "recomendado" y "decidido" por color, hasta Rojo/Negro (18) + danes.
 * - Adultos: solo "decidido" por color, hasta Rojo/Negro (10) + danes.
 *
 * Los grados negros (1º a 9º Dan) van tras Rojo/Negro y se incluyen en For Kids
 * y en Adultos (Tigers no llega a negro). Siembra idempotente por (escala, nombre).
 */
class GradosSeeder extends Seeder
{
    public function run(): void
    {
        $tigers = [
            ['Blanco', 'Blanco'],
            ['Blanco Tortuga', 'Blanco'],
            ['Naranjo', 'Naranjo'],
            ['Naranjo Tigre', 'Naranjo'],
            ['Amarillo', 'Amarillo'],
            ['Amarillo Chita', 'Amarillo'],
            ['Camuflado', 'Camuflado'],
            ['Camuflado León', 'Camuflado'],
            ['Verde', 'Verde'],
            ['Verde Águila', 'Verde'],
            ['Púrpura', 'Púrpura'],
            ['Púrpura Fénix', 'Púrpura'],
            ['Azul', 'Azul'],
            ['Azul Dragón', 'Azul'],
            ['Café', 'Café'],
            ['Café Cobra', 'Café'],
            ['Rojo', 'Rojo'],
            ['Rojo Pantera', 'Rojo'],
        ];

        $colores = ['Naranjo', 'Amarillo', 'Camuflado', 'Verde', 'Púrpura', 'Azul', 'Café', 'Rojo'];

        // For Kids: Blanco + (recomendado, decidido) por color + Rojo/Negro + danes.
        $forKids = [['Blanco', 'Blanco']];
        foreach ($colores as $color) {
            $forKids[] = ["{$color} Recomendado", $color];
            $forKids[] = ["{$color} Decidido", $color];
        }
        $forKids[] = ['Rojo/Negro', 'Rojo/Negro'];
        $forKids = array_merge($forKids, $this->danes());

        // Adultos: Blanco + decidido por color + Rojo/Negro + danes.
        $adultos = [['Blanco', 'Blanco']];
        foreach ($colores as $color) {
            $adultos[] = ["{$color} Decidido", $color];
        }
        $adultos[] = ['Rojo/Negro', 'Rojo/Negro'];
        $adultos = array_merge($adultos, $this->danes());

        $this->sembrarEscala(EscalaGrado::Tigers, $tigers);
        $this->sembrarEscala(EscalaGrado::ForKids, $forKids);
        $this->sembrarEscala(EscalaGrado::Adultos, $adultos);
    }

    /**
     * Grados negros: 1º a 9º Dan.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function danes(): array
    {
        $danes = [];
        foreach (range(1, 9) as $n) {
            $danes[] = ["{$n}º Dan", 'Negro'];
        }

        return $danes;
    }

    /**
     * Siembra una escala respetando el orden del arreglo.
     *
     * @param  array<int, array{0: string, 1: string}>  $grados
     */
    private function sembrarEscala(EscalaGrado $escala, array $grados): void
    {
        foreach ($grados as $orden => [$nombre, $color]) {
            Grado::updateOrCreate(
                ['escala' => $escala->value, 'nombre' => $nombre],
                ['orden' => $orden + 1, 'color' => $color, 'activo' => true],
            );
        }
    }
}
