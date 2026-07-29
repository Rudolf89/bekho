<?php

namespace Database\Seeders;

use App\Models\NivelLegacy;
use Illuminate\Database\Seeder;

/**
 * Catálogo del Programa Legacy: Niveles 1-3 (100 h cada uno) y sus requisitos.
 * Transversal (compartido). Idempotente por nombre de nivel y texto de requisito.
 *
 * Un requisito puede enlazarse a un cuestionario (prueba escrita autocorregida);
 * aquí se dejan como checklist manual y el examinador puede enlazar un
 * cuestionario cuando exista el banco de preguntas correspondiente.
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
                $nivel->requisitos()->updateOrCreate(
                    ['texto' => $texto],
                    ['orden' => $ordenReq + 1],
                );
            }
        }
    }
}
