<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Models\NivelLegacy;
use Illuminate\Database\Seeder;

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

        foreach ($niveles as $orden => $datos) {
            $nivel = NivelLegacy::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'orden' => $orden + 1,
                    'horas_requeridas' => 100,
                    'descripcion' => $datos['descripcion'],
                ],
            );

            foreach ($datos['requisitos'] as $ordenReq => $texto) {
                $esPruebaEscrita = str_contains($texto, 'Prueba escrita');

                $nivel->requisitos()->updateOrCreate(
                    ['texto' => $texto],
                    [
                        'orden' => $ordenReq + 1,
                        'cuestionario_id' => $esPruebaEscrita ? $pruebaN3?->id : null,
                    ],
                );
            }
        }
    }
}
