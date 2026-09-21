<?php

namespace Database\Seeders;

use App\Models\Federacion;
use App\Models\Forma;
use App\Models\Posicion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Formas Songahm (mano vacía) del Manual del Facilitador ATA Legacy v4. Cada
 * forma va a `formas` y sus movimientos a `pasos_forma`. Contenido VERIFICADO
 * (fuente/verificado=true). Debe correr DESPUÉS de ManualLegacySeeder (usa el
 * catálogo de posiciones). Idempotente: regenera los pasos en cada corrida.
 *
 * Las 12 formas de database/data/formas_songahm.json traen sus pasos. Las cinco
 * de cinturón negro que el manual NOMBRA pero no detalla (Sok Bong, Chung Hae,
 * Jahng Soo, Chul Joon, Jeong Seung) se crean SIN pasos y verificado=false: el
 * manual no trae su secuencia y no se inventa.
 */
class FormasPasosSeeder extends Seeder
{
    /** Formas de cinturón negro nombradas por el manual pero sin secuencia detallada. */
    private const FORMAS_SIN_DETALLE = ['Sok Bong', 'Chung Hae', 'Jahng Soo', 'Chul Joon', 'Jeong Seung'];

    /** Postura en palabras (formas por número) → código del catálogo de posiciones. */
    private const ALIAS_POSTURA = [
        'Frontal' => 'F',
        'Atrasada' => 'B',
        'Trasera' => 'B',
        'Media' => 'M',
        'Combate' => 'S',
        'Cerrada' => 'C',
    ];

    public function run(): void
    {
        $ruta = database_path('data/formas_songahm.json');
        if (! File::exists($ruta)) {
            $this->command->warn("No existe {$ruta}; se omiten las formas.");

            return;
        }

        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        // Catálogo de posiciones indexado por su código.
        $posiciones = [];
        foreach (Posicion::where('federacion_id', $federacion->id)->get() as $posicion) {
            $posiciones[(string) $posicion->codigo] = $posicion->id;
        }

        /** @var array<string, list<array{lado: string, tecnica: string, postura: string, seccion: string}>> $formas */
        $formas = json_decode(File::get($ruta), true);

        $orden = 0;
        foreach ($formas as $nombre => $pasos) {
            $orden++;
            $forma = Forma::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                [
                    'orden' => $orden,
                    'activo' => true,
                    'fuente' => ManualLegacySeeder::FUENTE,
                    'verificado' => true,
                ],
            );

            // Idempotente: se regeneran los pasos.
            $forma->pasos()->delete();
            foreach ($pasos as $i => $paso) {
                [$posicionId, $seccion] = $this->resolverPostura($paso['postura'], $paso['seccion'], $posiciones);

                $forma->pasos()->create([
                    'numero' => $i + 1,
                    'orden' => $i + 1,
                    'lado' => $paso['lado'] ?: null,
                    'tecnica' => $paso['tecnica'],
                    'posicion_id' => $posicionId,
                    'seccion' => $seccion,
                    'modificadores' => null, // el manual los trae dentro del texto del movimiento
                ]);
            }
        }

        // Formas de cinturón negro nombradas sin secuencia detallada.
        foreach (self::FORMAS_SIN_DETALLE as $nombre) {
            $orden++;
            Forma::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                [
                    'orden' => $orden,
                    'activo' => true,
                    'fuente' => ManualLegacySeeder::FUENTE,
                    'verificado' => false, // el manual la nombra pero no detalla sus pasos
                ],
            );
        }
    }

    /**
     * Resuelve la postura del paso a (posicion_id, seccion texto):
     * - Formas por número: postura en palabras → código; la sección viene aparte.
     * - Cinturón negro: postura como código(s) («M H», «-- M/H»); el primer token
     *   es la posición y el resto es la sección de altura.
     * - Vacía: patada (código «--»).
     *
     * @param  array<string, int>  $posiciones
     * @return array{0: int|null, 1: string|null}
     */
    private function resolverPostura(string $postura, string $seccion, array $posiciones): array
    {
        $postura = trim($postura);
        $seccion = trim($seccion);

        if ($postura === '') {
            // Patada: sin posición.
            return [$posiciones['--'] ?? null, $seccion ?: null];
        }

        if (isset(self::ALIAS_POSTURA[$postura])) {
            return [$posiciones[self::ALIAS_POSTURA[$postura]] ?? null, $seccion ?: null];
        }

        // Código(s) de cinturón negro: primer token = posición, resto = sección.
        $tokens = preg_split('/\s+/', $postura) ?: [];
        $codigo = $tokens[0] ?? '';
        $restoSeccion = count($tokens) > 1 ? implode(' ', array_slice($tokens, 1)) : '';

        return [$posiciones[$codigo] ?? null, ($seccion ?: $restoSeccion) ?: null];
    }
}
