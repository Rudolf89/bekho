<?php

namespace Database\Seeders;

use App\Enums\TipoAtributoTecnico;
use App\Models\Arma;
use App\Models\AtributoTecnico;
use App\Models\Federacion;
use App\Models\HabilidadVida;
use App\Models\Posicion;
use App\Models\SeccionAltura;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Catálogos del Manual del Facilitador ATA Legacy v4 (julio 2018), contenido
 * VERIFICADO contra el manual: leyenda de formas (posiciones, secciones de
 * altura), habilidades para la vida, atributos técnicos y armas Protech.
 *
 * Fuente única marcada en cada fila (fuente/verificado=true). Donde el JSON trae
 * null, se siembra null: no se completa lo que el manual no dice. Idempotente.
 */
class ManualLegacySeeder extends Seeder
{
    /** Procedencia común de todo el contenido de este seeder. */
    public const FUENTE = 'Manual ATA Legacy v4 (julio 2018)';

    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        $this->sembrarPosicionesYSecciones($federacion->id);
        $this->sembrarHabilidadesVida($federacion->id);
        $this->sembrarAtributos($federacion->id);
        $this->sembrarArmas($federacion->id);
    }

    /**
     * @param  string  $archivo  nombre del archivo en database/data/
     * @return array<string, mixed>|null
     */
    private function leer(string $archivo): ?array
    {
        $ruta = database_path("data/{$archivo}");

        if (! File::exists($ruta)) {
            $this->command?->warn("No existe {$ruta}; se omite.");

            return null;
        }

        return json_decode(File::get($ruta), true);
    }

    private function sembrarPosicionesYSecciones(int $federacionId): void
    {
        $datos = $this->leer('posiciones_secciones.json');
        if (! $datos) {
            return;
        }

        foreach ($datos['posiciones'] ?? [] as $p) {
            Posicion::updateOrCreate(
                ['federacion_id' => $federacionId, 'codigo' => $p['codigo']],
                ['nombre' => $p['nombre'], 'fuente' => self::FUENTE, 'verificado' => true],
            );
        }

        foreach ($datos['secciones'] ?? [] as $s) {
            SeccionAltura::updateOrCreate(
                ['federacion_id' => $federacionId, 'codigo' => $s['codigo']],
                ['nombre' => $s['nombre'], 'fuente' => self::FUENTE, 'verificado' => true],
            );
        }
    }

    private function sembrarHabilidadesVida(int $federacionId): void
    {
        $datos = $this->leer('habilidades_vida.json');
        if (! $datos) {
            return;
        }

        foreach ($datos['habilidades'] ?? [] as $h) {
            HabilidadVida::updateOrCreate(
                ['federacion_id' => $federacionId, 'nombre' => $h['nombre']],
                [
                    'definicion' => $h['definicion'] ?? null,
                    'orden' => $h['orden'] ?? 0,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }

    private function sembrarAtributos(int $federacionId): void
    {
        $datos = $this->leer('atributos_tecnicos.json');
        if (! $datos) {
            return;
        }

        foreach ($datos['atributos'] ?? [] as $a) {
            AtributoTecnico::updateOrCreate(
                ['federacion_id' => $federacionId, 'tipo' => TipoAtributoTecnico::Atributo->value, 'nombre' => $a['nombre']],
                [
                    'definicion' => $a['definicion'] ?? null,
                    'orden' => $a['orden'] ?? 0,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }

        // Criterios de conocimiento de forma: el manual solo trae el nombre; la
        // definición queda null (no se inventa).
        foreach ($datos['criterios_conocimiento_forma'] ?? [] as $c) {
            AtributoTecnico::updateOrCreate(
                ['federacion_id' => $federacionId, 'tipo' => TipoAtributoTecnico::CriterioForma->value, 'nombre' => $c['nombre']],
                [
                    'definicion' => $c['definicion'] ?? null,
                    'orden' => $c['orden'] ?? 0,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }

    private function sembrarArmas(int $federacionId): void
    {
        $datos = $this->leer('armas_protech.json');
        if (! $datos) {
            return;
        }

        foreach ($datos['armas'] ?? [] as $a) {
            Arma::updateOrCreate(
                ['federacion_id' => $federacionId, 'abreviatura' => $a['abreviatura']],
                [
                    'nombre' => $a['nombre'],
                    'nombre_comun' => $a['nombre_comun'] ?? null,
                    'modalidad' => $a['modalidad'] ?? null,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }
}
