<?php

namespace Database\Seeders;

use App\Models\EtapaPrograma;
use App\Models\Federacion;
use App\Models\Programa;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración del módulo de Formación dentro del grupo BEKHO.
 *
 * El contenido es genérico de ejemplo (no material ATA oficial), solo para
 * poder mostrar el módulo funcionando.
 */
class FormacionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();
        if (! $federacion) {
            return;
        }

        // Contenido genérico de ejemplo: va al programa contenedor "Aprender (general)".
        $programa = Programa::firstOrCreate(
            ['nombre' => 'Aprender (general)'],
            ['tipo' => 'formacion', 'federacion_id' => $federacion->id, 'descripcion' => 'Contenido de estudio sin programa propio.', 'activo' => true, 'orden' => 95],
        );

        $niveles = [
            [
                'nombre' => 'Nivel 1 — Fundamentos del instructor',
                'descripcion' => 'Bases para quienes recién comienzan su formación como instructores.',
                'contenidos' => [
                    ['titulo' => 'Bienvenida al programa', 'tipo' => 'texto', 'cuerpo' => "Este es un texto de ejemplo de bienvenida.\n\nAquí el grupo redactará la introducción al programa de formación de instructores."],
                    ['titulo' => 'Rol y ética del instructor', 'tipo' => 'texto', 'cuerpo' => 'Contenido de ejemplo sobre el rol del instructor, valores y responsabilidades frente a los alumnos.'],
                    ['titulo' => 'Video: postura y saludo', 'tipo' => 'video', 'url_recurso' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                    ['titulo' => 'Guía de terminología (documento)', 'tipo' => 'documento', 'url_recurso' => 'https://example.com/docs/terminologia.pdf'],
                ],
            ],
            [
                'nombre' => 'Nivel 2 — Enseñanza en clase',
                'descripcion' => 'Cómo estructurar y conducir una clase con distintos grupos de alumnos.',
                'contenidos' => [
                    ['titulo' => 'Estructura de una clase', 'tipo' => 'texto', 'cuerpo' => 'Texto de ejemplo: calentamiento, parte central y cierre. El grupo completará el detalle.'],
                    ['titulo' => 'Video: dinámicas para niños', 'tipo' => 'video', 'url_recurso' => 'https://vimeo.com/76979871'],
                    ['titulo' => 'Manejo de grupos mixtos', 'tipo' => 'texto', 'cuerpo' => 'Contenido de ejemplo sobre cómo atender simultáneamente distintos niveles en una misma clase.'],
                ],
            ],
            [
                'nombre' => 'Nivel 3 — Evaluación y progreso',
                'descripcion' => 'Seguimiento del avance de los alumnos y preparación de exámenes de grado.',
                'contenidos' => [
                    ['titulo' => 'Criterios de evaluación', 'tipo' => 'texto', 'cuerpo' => 'Texto de ejemplo con los criterios que el grupo definirá para evaluar el progreso.'],
                    ['titulo' => 'Video: corrección de técnicas', 'tipo' => 'video', 'url_recurso' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
                    ['titulo' => 'Planilla de seguimiento (documento)', 'tipo' => 'documento', 'url_recurso' => 'https://example.com/docs/planilla-seguimiento.pdf'],
                    ['titulo' => 'Preparación de un examen', 'tipo' => 'texto', 'cuerpo' => 'Contenido de ejemplo sobre cómo preparar a un alumno para su examen de grado.'],
                ],
            ],
        ];

        foreach ($niveles as $ordenNivel => $datosNivel) {
            $etapa = EtapaPrograma::updateOrCreate(
                ['programa_id' => $programa->id, 'nombre' => $datosNivel['nombre']],
                ['descripcion' => $datosNivel['descripcion'], 'orden' => $ordenNivel, 'activo' => true],
            );

            foreach ($datosNivel['contenidos'] as $ordenContenido => $datosContenido) {
                $etapa->contenidos()->updateOrCreate(
                    ['titulo' => $datosContenido['titulo']],
                    [
                        'tipo' => $datosContenido['tipo'],
                        'cuerpo' => $datosContenido['cuerpo'] ?? null,
                        'url_recurso' => $datosContenido['url_recurso'] ?? null,
                        'orden' => $ordenContenido,
                        'activo' => true,
                    ],
                );
            }
        }
    }
}
