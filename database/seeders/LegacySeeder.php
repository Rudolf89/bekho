<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Models\NivelLegacy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Catálogo del Programa Legacy: Niveles 1-3 (100 h cada uno) y sus requisitos.
 * Transversal (compartido). Idempotente por nombre de nivel y texto de requisito.
 *
 * Un requisito puede enlazarse a un cuestionario (prueba escrita autocorregida):
 * "Prueba escrita de Nivel 3 aprobada" se enlaza al banco de Legacy N3 (sembrado
 * por CuestionariosSeeder), de modo que se cumple con un intento aprobado.
 */
class LegacySeeder extends Seeder
{
    public function run(): void
    {
        $niveles = [
            [
                'nombre' => 'Legacy Nivel 1',
                'edad_minima' => 13,
                'descripcion' => 'Primer nivel del track de formación de instructores: fundamentos de enseñanza y asistencia acreditada.',
                'requisitos' => [
                    'Membresía ATA vigente',
                    'Certificación de Protección Juvenil (Youth Protection)',
                    '100 horas de asistencia acreditadas',
                    'Plan de estudios demostrado (CC)',
                ],
            ],
            [
                'nombre' => 'Legacy Nivel 2',
                'edad_minima' => 16,
                'descripcion' => 'Segundo nivel: conducción de clase y evaluación de alumnos.',
                'requisitos' => [
                    'Membresía ATA vigente',
                    '100 horas de asistencia acreditadas',
                    'Plan de estudios demostrado (CC)',
                    'Conducción de clase supervisada',
                ],
            ],
            [
                'nombre' => 'Legacy Nivel 3',
                'edad_minima' => 18,
                'descripcion' => 'Tercer nivel: requisito para rendir 4º grado y superior. Incluye prueba escrita.',
                'requisitos' => [
                    'Membresía ATA vigente',
                    '100 horas de asistencia acreditadas',
                    'Plan de estudios demostrado (CC)',
                    'Prueba escrita de Nivel 3 aprobada',
                ],
            ],
        ];

        // Prueba escrita N3 → cuestionario del banco de Legacy (si ya se sembró).
        $pruebaN3 = Cuestionario::where('titulo', 'Examen escrito · Programa Legacy Nivel 3')->first();

        // Requisitos Protech por nivel, desde el Manual ATA Legacy (armas_protech.json).
        $protech = $this->protechPorNivel();

        foreach ($niveles as $orden => $datos) {
            $nivel = NivelLegacy::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'orden' => $orden + 1,
                    'horas_requeridas' => 100,
                    'edad_minima' => $datos['edad_minima'],
                    'descripcion' => $datos['descripcion'],
                    'fuente' => ManualLegacySeeder::FUENTE,
                    'verificado' => true,
                ],
            );

            $ordenReq = 0;
            foreach ($datos['requisitos'] as $texto) {
                $ordenReq++;
                $esPruebaEscrita = str_contains($texto, 'Prueba escrita');

                $nivel->requisitos()->updateOrCreate(
                    ['texto' => $texto],
                    [
                        'orden' => $ordenReq,
                        'cuestionario_id' => $esPruebaEscrita ? $pruebaN3?->id : null,
                        'fuente' => ManualLegacySeeder::FUENTE,
                        'verificado' => true,
                    ],
                );
            }

            // Requisitos Protech (armas) del nivel, como contenido del manual.
            foreach ($protech[$orden + 1] ?? [] as $texto) {
                $ordenReq++;
                $nivel->requisitos()->updateOrCreate(
                    ['texto' => $texto],
                    ['orden' => $ordenReq, 'cuestionario_id' => null, 'fuente' => ManualLegacySeeder::FUENTE, 'verificado' => true],
                );
            }
        }
    }

    /**
     * Requisitos Protech por número de nivel (1, 2, 3), como textos, desde
     * database/data/armas_protech.json. Devuelve [] si el archivo no existe.
     *
     * @return array<int, list<string>>
     */
    private function protechPorNivel(): array
    {
        $ruta = database_path('data/armas_protech.json');
        if (! File::exists($ruta)) {
            return [];
        }

        $datos = json_decode(File::get($ruta), true);
        $porNivel = [];

        foreach ($datos['requisitos_por_nivel'] ?? [] as $bloque) {
            $nivel = (int) ($bloque['nivel'] ?? 0);
            $textos = [];

            if (! empty($bloque['condicion_general'])) {
                $textos[] = 'Protech (condición general): '.$bloque['condicion_general'];
            }

            foreach ($bloque['items'] ?? [] as $item) {
                $arma = $item['arma'] ?? null;
                $detalle = $item['detalle'] ?? null;

                $textos[] = match (true) {
                    $arma && $detalle => "Protech: {$arma} — {$detalle}",
                    (bool) $arma => "Protech: {$arma}",
                    (bool) $detalle => "Protech: {$detalle}",
                    default => 'Protech',
                };
            }

            $porNivel[$nivel] = $textos;
        }

        return $porNivel;
    }
}
