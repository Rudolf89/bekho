<?php

namespace Database\Seeders;

use App\Enums\CategoriaJuramento;
use App\Enums\MomentoJuramento;
use App\Models\Federacion;
use App\Models\Juramento;
use Illuminate\Database\Seeder;

/**
 * Juramentos que la federación confirmó (Espíritu Songahm de inicio y de cierre
 * para Kids y Adultos, y el juramento Tigers para ambos momentos). Idempotente.
 * Los gestos del juramento Tigers van entre paréntesis dentro del texto (sin
 * tabla de líneas ni de gestos: el texto completo basta).
 */
class JuramentosSeeder extends Seeder
{
    /** Procedencia: textos validados por la federación. */
    public const FUENTE = 'Confirmado por la federación';

    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        $juramentos = [
            [
                'nombre' => 'Espíritu Songahm del Taekwondo',
                'categoria_clase' => CategoriaJuramento::KidsAdultos,
                'momento' => MomentoJuramento::Inicio,
                'orden' => 1,
                'texto' => '¡Señor! Practicaré en el Espíritu del Taekwondo, con cortesía hacia mis compañeros, lealtad a mi instructor, y respeto por mis inferiores y superiores.',
            ],
            [
                'nombre' => 'Espíritu Songahm',
                'categoria_clase' => CategoriaJuramento::KidsAdultos,
                'momento' => MomentoJuramento::Cierre,
                'orden' => 2,
                'texto' => '¡Señor! Viviré con perseverancia en el espíritu del Taekwondo, en honor a los demás, integridad conmigo mismo, y autocontrol en mis acciones.',
            ],
            [
                'nombre' => 'Juramento Tigers',
                'categoria_clase' => CategoriaJuramento::Tigers,
                'momento' => MomentoJuramento::Ambos,
                'orden' => 3,
                'texto' => '¡Señor! Prometo ser una buena persona, (Mano derecha levantada) con conocimiento en mi mente, (Apuntando a la cabeza) ¡honestidad en mi corazón!, (Mano sobre el corazón) y fuerza en mi cuerpo. (Mostrando el músculo del brazo) ¡Hacer buenos amigos (Gesto de dar la mano) y convertirme en un líder Cinturón Negro! (Tomando el propio cinturón) ¡Señor!',
            ],
        ];

        foreach ($juramentos as $j) {
            Juramento::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $j['nombre']],
                [
                    'categoria_clase' => $j['categoria_clase']->value,
                    'momento' => $j['momento']->value,
                    'texto' => $j['texto'],
                    'orden' => $j['orden'],
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }
}
