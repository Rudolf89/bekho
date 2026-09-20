<?php

namespace Database\Seeders;

use App\Models\Contenido;
use App\Models\Cuestionario;
use App\Models\EtapaPrograma;
use App\Models\Federacion;
use App\Models\Grado;
use App\Models\Nivel;
use App\Models\Programa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Unificación LMS + Legacy — commit (a): fusión del catálogo en programas/etapas.
 *
 * Idempotente (catálogo reseeded). Hace tres cosas:
 *  1. Fija `federacion_id` en todos los programas (son catálogo de la federación).
 *  2. Siembra las etapas del programa Legacy desde `legacy_niveles.json`
 *     (3 etapas de 100 h, edades 13/16/18, grado mínimo 1.er Dan en la etapa 3)
 *     con sus requisitos (la prueba escrita se enlaza al banco de Legacy N3).
 *  3. Fusiona los `niveles` del LMS en `etapas_programa`: cada manual pasa a ser
 *     un programa (Tigers/MAK/MAX/Juez; Legacy reutiliza el suyo), y lo que no
 *     encaja va a un programa CONTENEDOR "Aprender (general)". Los contenidos
 *     quedan enlazados a su etapa (además de conservar nivel_id durante el expand).
 *
 * NO reescribe niveles/niveles_legacy: el modelo viejo sigue vivo hasta el
 * commit (d). Corre dentro del comoSistema del DatabaseSeeder; usa
 * withoutGlobalScopes para poder leer/escribir niveles y contenidos de todos los
 * grupos también cuando se invoca suelto en un test.
 */
class EtapasProgramaSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();

        if ($federacion) {
            Programa::query()->whereNull('federacion_id')->update(['federacion_id' => $federacion->id]);
        }

        $this->sembrarLegacy($federacion);
        $this->fusionarNivelesLms($federacion);
    }

    /**
     * Etapas y requisitos del programa Legacy desde legacy_niveles.json.
     */
    private function sembrarLegacy(?Federacion $federacion): void
    {
        $legacy = Programa::query()->where('nombre', 'Legacy')->first();
        $ruta = database_path('data/legacy_niveles.json');

        if (! $legacy || ! File::exists($ruta)) {
            return;
        }

        $datos = json_decode(File::get($ruta), true);
        $fuente = $datos['fuente'] ?? null;

        // Grado mínimo (1.er Dan) para la etapa que lo exige.
        $primerDan = Grado::query()->where('nombre', '1º Dan')->first();
        // Prueba escrita de Nivel 3 → banco de Legacy (si CuestionariosSeeder ya corrió).
        $pruebaN3 = Cuestionario::query()->where('titulo', 'Examen escrito · Programa Legacy Nivel 3')->first();

        $legacy->update(['fuente' => $fuente, 'verificado' => true]);

        foreach ($datos['niveles'] ?? [] as $n) {
            $etapa = EtapaPrograma::updateOrCreate(
                ['programa_id' => $legacy->id, 'nombre' => $n['nombre']],
                [
                    'orden' => $n['orden'],
                    'horas_requeridas' => $n['horas_requeridas'] ?? null,
                    'edad_minima' => $n['edad_minima'] ?? null,
                    // El manual solo fija grado mínimo (1.er Dan) en el Nivel 3.
                    'grado_minimo_id' => ! empty($n['grado_minimo']) ? $primerDan?->id : null,
                    'activo' => true,
                    'fuente' => $fuente,
                    'verificado' => true,
                ],
            );

            $orden = 0;
            foreach ($n['requisitos'] ?? [] as $texto) {
                $orden++;
                $esEscrito = stripos($texto, 'escrito') !== false;

                $etapa->requisitos()->updateOrCreate(
                    ['descripcion' => $texto],
                    [
                        'tipo' => $esEscrito ? 'cuestionario' : 'manual',
                        'cuestionario_id' => $esEscrito ? $pruebaN3?->id : null,
                        'orden' => $orden,
                    ],
                );
            }

            // El banco de Legacy N3 queda enlazado a la etapa que lo evalúa.
            if ($pruebaN3 && ! empty($n['examen_escrito'])) {
                $pruebaN3->update(['etapa_programa_id' => $etapa->id]);
            }
        }
    }

    /**
     * Cada `nivel` del LMS pasa a ser una etapa de su programa. Los contenidos se
     * enlazan a la etapa. Se deduplica por nombre de nivel (los manuales son
     * catálogo de la federación, aunque hoy los niveles lleven grupo_id).
     */
    private function fusionarNivelesLms(?Federacion $federacion): void
    {
        $porNombre = Nivel::withoutGlobalScopes()->get()->groupBy('nombre');
        $ordenContenedor = 0;

        foreach ($porNombre as $nombre => $niveles) {
            [$programaNombre, $descripcion] = $this->mapearPrograma((string) $nombre);

            $programa = Programa::firstOrCreate(
                ['nombre' => $programaNombre],
                [
                    'tipo' => 'formacion',
                    'federacion_id' => $federacion?->id,
                    'descripcion' => $descripcion,
                    'activo' => true,
                    'orden' => 90 + $ordenContenedor++,
                ],
            );

            if ($federacion && ! $programa->federacion_id) {
                $programa->update(['federacion_id' => $federacion->id]);
            }

            $etapa = EtapaPrograma::updateOrCreate(
                ['programa_id' => $programa->id, 'nombre' => $nombre],
                [
                    'orden' => (int) ($niveles->min('orden') ?? 0),
                    'descripcion' => $niveles->first()->descripcion,
                    'activo' => true,
                ],
            );

            Contenido::withoutGlobalScopes()
                ->whereIn('nivel_id', $niveles->pluck('id'))
                ->update(['etapa_programa_id' => $etapa->id]);
        }
    }

    /**
     * Programa (nombre, descripción) al que pertenece un nivel del LMS, por su
     * nombre. Lo que no encaja va al contenedor "Aprender (general)".
     *
     * @return array{0: string, 1: string}
     */
    private function mapearPrograma(string $nombre): array
    {
        $n = Str::lower($nombre);

        return match (true) {
            Str::contains($n, 'legacy') => ['Legacy', 'Formación de instructores; ingreso desde los 9 años.'],
            Str::contains($n, 'tigers') => ['Tigers', 'Programa ATA Tigers (preescolar y kínder).'],
            Str::contains($n, 'mak') => ['MAK', 'Programa MAK (Martial Arts Kids).'],
            Str::contains($n, 'max') || Str::contains($n, 'xtreme') => ['Xtreme', 'Disciplina paralela: acrobacias y armas (currículo MAX).'],
            Str::contains($n, 'juez') => ['Preparación para examen de juez', 'Estudio del Manual del Juez ATA.'],
            default => ['Aprender (general)', 'Contenedor de niveles de Aprender sin programa propio.'],
        };
    }
}
