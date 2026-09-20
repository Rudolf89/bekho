<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Enums\NivelEntrenamiento;
use App\Enums\TipoGrado;
use App\Models\Federacion;
use App\Models\Grado;
use App\Models\TramoEntrenamiento;
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

        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );
        // Mapa clave de tramo => id, para enlazar cada grado a su tramo. Si aún
        // no hay tramos sembrados, queda vacío y tramo_id se deja en null.
        $tramos = TramoEntrenamiento::where('federacion_id', $federacion->id)->pluck('id', 'clave')->all();

        $this->sembrarEscala(EscalaGrado::Tigers, $tigers, $federacion->id, $tramos);
        $this->sembrarEscala(EscalaGrado::ForKids, $forKids, $federacion->id, $tramos);
        $this->sembrarEscala(EscalaGrado::Adultos, $adultos, $federacion->id, $tramos);
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
     * @param  array<string, int>  $tramos  [clave de tramo => id]
     */
    private function sembrarEscala(EscalaGrado $escala, array $grados, int $federacionId, array $tramos): void
    {
        foreach ($grados as $orden => [$nombre, $color]) {
            $tipo = TipoGrado::desdeNombre($nombre);
            [$franjas, $estrellas] = self::insigniasDe($nombre, $tipo);
            $nivel = self::nivelDeColor($color);
            $requiereNominacion = in_array($nivel, [NivelEntrenamiento::RojoNegro, NivelEntrenamiento::Danes], true);

            Grado::updateOrCreate(
                ['escala' => $escala->value, 'nombre' => $nombre],
                [
                    'federacion_id' => $federacionId,
                    'tramo_id' => $tramos[$nivel->value] ?? null,
                    'orden' => $orden + 1,
                    'color' => $color,
                    'tipo' => $tipo->value,
                    'franjas' => $franjas,
                    'estrellas' => $estrellas,
                    // Tiempo sugerido entre grados: 2 meses en los de color; los
                    // rojo-negro y danes van por nominación, sin plazo fijo.
                    'meses_sugeridos' => $requiereNominacion ? null : 2,
                    'requiere_nominacion' => $requiereNominacion,
                    'significado' => self::SIGNIFICADOS[$color] ?? null,
                    'activo' => true,
                ],
            );
        }
    }

    /**
     * Tramo de entrenamiento según el color del cinturón (mismo corte que
     * App\Models\Grado::nivelEntrenamiento()).
     */
    private static function nivelDeColor(string $color): NivelEntrenamiento
    {
        return match ($color) {
            'Camuflado', 'Verde', 'Púrpura' => NivelEntrenamiento::Intermedio,
            'Azul', 'Café', 'Rojo' => NivelEntrenamiento::Avanzado,
            'Rojo/Negro' => NivelEntrenamiento::RojoNegro,
            'Negro' => NivelEntrenamiento::Danes,
            default => NivelEntrenamiento::Principiantes,
        };
    }

    /**
     * Insignias del cinturón negro: los danes 1º-4º llevan una franja roja por
     * grado; desde el 5º Dan se usan estrellas (una por grado sobre el 4º). Los
     * cinturones de color no llevan ninguna. Devuelve [franjas, estrellas].
     *
     * @return array{0: int, 1: int}
     */
    private static function insigniasDe(string $nombre, TipoGrado $tipo): array
    {
        if ($tipo !== TipoGrado::Dan || ! preg_match('/^(\d+)/', $nombre, $m)) {
            return [0, 0];
        }

        $dan = (int) $m[1];

        return $dan >= 5 ? [0, $dan - 4] : [$dan, 0];
    }

    /**
     * Significado del color (filosofía Songahm: el crecimiento del pino).
     */
    private const SIGNIFICADOS = [
        'Blanco' => 'Como ocurre con el pino, ahora debe plantarse la semilla y nutrirse para que desarrolle raíces fuertes.',
        'Naranjo' => 'El sol comienza a salir. Como en el amanecer, solo se aprecia la belleza de la salida del sol, todavía no su inmenso poder.',
        'Amarillo' => 'La semilla comienza a ver la luz del sol.',
        'Camuflado' => 'El retoño se oculta entre los pinos más altos y ahora debe abrirse camino hacia arriba.',
        'Verde' => 'El pino empieza a desarrollarse y a ganar fuerza.',
        'Púrpura' => 'Llegando a la montaña. El árbol está a mitad de su crecimiento y ahora el camino se vuelve empinado.',
        'Azul' => 'El árbol se eleva hacia el cielo, buscando nuevas alturas.',
        'Café' => 'El árbol está firmemente arraigado en la tierra.',
        'Rojo' => 'El sol se pone. La primera etapa de crecimiento se ha cumplido.',
        'Rojo/Negro' => 'El amanecer de un nuevo día. El sol atraviesa la oscuridad.',
        'Negro' => 'El árbol ha alcanzado la madurez y ha vencido la oscuridad… Ahora debe comenzar a plantar semillas para el futuro.',
    ];
}
