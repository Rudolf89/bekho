<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Support\Cuestionarios\BancoJuez;
use Illuminate\Database\Seeder;

/**
 * Siembra los cuestionarios base del módulo de evaluaciones (catálogo compartido,
 * sin academia_id): el examen de Juez ATA (N1, N2, N3 y repaso de puntuación).
 *
 * El módulo es genérico: cualquier examinador puede crear otros cuestionarios
 * desde la interfaz. Este seeder solo carga el banco de juez como punto de partida.
 *
 * Idempotente: reconstruye las preguntas de cada cuestionario en cada corrida.
 */
class CuestionariosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (BancoJuez::cuestionarios() as $orden => $datos) {
            $cuestionario = Cuestionario::updateOrCreate(
                ['titulo' => $datos['titulo']],
                [
                    'descripcion' => $datos['descripcion'],
                    'area' => $datos['area'],
                    'umbral_aprobacion' => 80,
                    'activo' => true,
                    'orden' => $orden,
                ],
            );

            // Idempotente: se regeneran las preguntas (y en cascada, sus opciones).
            $cuestionario->preguntas()->delete();

            foreach ($datos['preguntas'] as $ordenPregunta => $pregunta) {
                $modelo = $cuestionario->preguntas()->create([
                    'enunciado' => $pregunta['q'],
                    'explicacion' => $pregunta['why'] ?? null,
                    'nota' => ! empty($pregunta['flag'])
                        ? 'Pregunta ambigua o dependiente de la versión del examen; verifica con tu instructor.'
                        : null,
                    'orden' => $ordenPregunta,
                ]);

                foreach ($pregunta['options'] as $ordenOpcion => $texto) {
                    $modelo->opciones()->create([
                        'texto' => $texto,
                        'correcta' => $ordenOpcion === $pregunta['correct'],
                        'orden' => $ordenOpcion,
                    ]);
                }
            }
        }
    }
}
