<?php

namespace Database\Seeders;

use App\Models\Contenido;
use App\Models\EtapaPrograma;
use App\Models\Federacion;
use App\Models\Programa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Contenido de "Aprender" a partir de los manuales ATA en español (Legacy,
 * Tigers, MAK, MAX N1 y N2). Cada manual es una ETAPA de su programa con:
 *   - una introducción de estudio,
 *   - un Contenido de tipo texto por sección del manual,
 *   - un Contenido de tipo documento con el enlace al manual oficial en Drive.
 *
 * Catálogo de la federación (sin grupo_id). La fuente estructurada vive en
 * database/data/manuales/*.json. Idempotente (updateOrCreate).
 */
class ManualesAprenderSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();
        if (! $federacion) {
            return;
        }

        foreach ($this->manuales() as $manual) {
            $this->sembrarManual($federacion, $manual);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function manuales(): array
    {
        $dir = database_path('data/manuales');

        if (! File::isDirectory($dir)) {
            return [];
        }

        $manuales = [];

        foreach (File::files($dir) as $archivo) {
            if ($archivo->getExtension() !== 'json') {
                continue;
            }

            $datos = json_decode(File::get($archivo->getPathname()), true);

            if (! is_array($datos) || $datos === []) {
                continue;
            }

            // El JSON de cada manual es un objeto; se normalizan las claves a
            // texto porque json_decode() no lo promete.
            $manual = [];
            foreach ($datos as $clave => $valor) {
                $manual[(string) $clave] = $valor;
            }

            $manuales[] = $manual;
        }

        usort($manuales, fn (array $a, array $b) => ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0));

        return $manuales;
    }

    /**
     * @param  array<string, mixed>  $manual
     */
    private function sembrarManual(Federacion $federacion, array $manual): void
    {
        [$programaNombre, $descripcionPrograma] = $this->mapearPrograma((string) $manual['nivel']);

        $programa = Programa::firstOrCreate(
            ['nombre' => $programaNombre],
            ['tipo' => 'formacion', 'federacion_id' => $federacion->id, 'descripcion' => $descripcionPrograma, 'activo' => true, 'orden' => 90],
        );

        $etapa = EtapaPrograma::updateOrCreate(
            ['programa_id' => $programa->id, 'nombre' => $manual['nivel']],
            ['descripcion' => $manual['descripcion'] ?? null, 'orden' => $manual['orden'] ?? 50, 'activo' => true],
        );

        $orden = 0;

        // 1) Introducción de estudio.
        $etapa->contenidos()->updateOrCreate(
            ['titulo' => 'Sobre este manual'],
            [
                'descripcion' => 'Cómo estudiar este material.',
                'tipo' => 'texto',
                'cuerpo' => ($manual['descripcion'] ?? '')."\n\n"
                    .'Este material resume el manual oficial en secciones de estudio. Léelo por partes y '
                    .'revisa el documento original enlazado al final para el detalle completo.',
                'orden' => $orden++,
                'activo' => true,
            ],
        );

        // 2) Una lección de texto por sección del manual.
        foreach ($manual['secciones'] ?? [] as $seccion) {
            $cuerpo = implode("\n\n", array_map(fn (string $p) => '• '.$p, $seccion['puntos'] ?? []));

            $etapa->contenidos()->updateOrCreate(
                ['titulo' => $seccion['titulo']],
                ['descripcion' => null, 'tipo' => 'texto', 'cuerpo' => $cuerpo, 'orden' => $orden++, 'activo' => true],
            );
        }

        // 3) Documento oficial en Drive.
        if (! empty($manual['doc_url'])) {
            $etapa->contenidos()->updateOrCreate(
                ['titulo' => 'Documento oficial'],
                [
                    'descripcion' => $manual['doc_titulo'] ?? 'Manual oficial (Google Drive).',
                    'tipo' => 'documento',
                    'url_recurso' => $manual['doc_url'],
                    'orden' => $orden++,
                    'activo' => true,
                ],
            );
        }
    }

    /**
     * Programa (nombre, descripción) al que pertenece un manual, por su nombre.
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
            default => ['Aprender (general)', 'Contenido de estudio sin programa propio.'],
        };
    }
}
