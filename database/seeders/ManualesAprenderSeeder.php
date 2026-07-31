<?php

namespace Database\Seeders;

use App\Models\Academia;
use App\Models\Contenido;
use App\Models\Nivel;
use App\Support\Tenancy\Academia as Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Contenido de "Aprender" (LMS) a partir de los manuales ATA en español (Legacy,
 * Tigers, MAK, MAX N1 y N2). Cada manual se vuelca como un Nivel con:
 *   - una introducción de estudio,
 *   - un Contenido de tipo texto por cada sección del manual (puntos de estudio),
 *   - un Contenido de tipo documento con el enlace al manual oficial en Drive.
 *
 * La fuente estructurada vive en database/data/manuales/*.json (transcrita de los
 * manuales, sin inventar). Idempotente (updateOrCreate). Corre DESPUÉS de
 * RolesPermisosSeeder (necesita la academia BEKHO).
 */
class ManualesAprenderSeeder extends Seeder
{
    public function run(): void
    {
        $academia = Academia::where('nombre', 'BEKHO Power Academy')->first();

        if (! $academia) {
            $this->command?->warn('No existe la academia BEKHO; ejecuta antes RolesPermisosSeeder.');

            return;
        }

        Tenant::set($academia->id);

        foreach ($this->manuales() as $manual) {
            $this->sembrarManual($academia, $manual);
        }

        Tenant::olvidar();
    }

    /**
     * Carga los manuales desde los JSON, ordenados por el campo "orden".
     *
     * @return list<array<string, mixed>>
     */
    private function manuales(): array
    {
        $dir = database_path('data/manuales');

        if (! File::isDirectory($dir)) {
            return [];
        }

        $manuales = collect(File::files($dir))
            ->filter(fn ($f) => $f->getExtension() === 'json')
            ->map(fn ($f) => json_decode(File::get($f->getPathname()), true))
            ->filter()
            ->sortBy('orden')
            ->values()
            ->all();

        return $manuales;
    }

    /**
     * @param  array<string, mixed>  $manual
     */
    private function sembrarManual(Academia $academia, array $manual): void
    {
        $nivel = Nivel::updateOrCreate(
            ['academia_id' => $academia->id, 'nombre' => $manual['nivel']],
            [
                'descripcion' => $manual['descripcion'] ?? null,
                'orden' => $manual['orden'] ?? 50,
                'activo' => true,
            ],
        );

        $orden = 0;

        // 1) Introducción de estudio.
        Contenido::updateOrCreate(
            ['nivel_id' => $nivel->id, 'titulo' => 'Sobre este manual'],
            [
                'academia_id' => $academia->id,
                'descripcion' => 'Cómo estudiar este material.',
                'tipo' => 'texto',
                'cuerpo' => ($manual['descripcion'] ?? '')."\n\n"
                    .'Este material resume el manual oficial en secciones de estudio. Léelo por partes y '
                    .'revisa el documento original enlazado al final para el detalle completo.',
                'orden' => $orden++,
                'activo' => true,
            ],
        );

        // 2) Una lección de texto por cada sección del manual.
        foreach ($manual['secciones'] ?? [] as $seccion) {
            $cuerpo = implode("\n\n", array_map(
                fn (string $p) => '• '.$p,
                $seccion['puntos'] ?? [],
            ));

            Contenido::updateOrCreate(
                ['nivel_id' => $nivel->id, 'titulo' => $seccion['titulo']],
                [
                    'academia_id' => $academia->id,
                    'descripcion' => null,
                    'tipo' => 'texto',
                    'cuerpo' => $cuerpo,
                    'orden' => $orden++,
                    'activo' => true,
                ],
            );
        }

        // 3) Documento oficial en Drive.
        if (! empty($manual['doc_url'])) {
            Contenido::updateOrCreate(
                ['nivel_id' => $nivel->id, 'titulo' => 'Documento oficial'],
                [
                    'academia_id' => $academia->id,
                    'descripcion' => $manual['doc_titulo'] ?? 'Manual oficial (Google Drive).',
                    'tipo' => 'documento',
                    'url_recurso' => $manual['doc_url'],
                    'orden' => $orden++,
                    'activo' => true,
                ],
            );
        }
    }
}
