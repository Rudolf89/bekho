<?php

namespace Database\Seeders;

use App\Models\Academia;
use App\Models\Contenido;
use App\Models\Nivel;
use App\Support\Tenancy\Academia as Tenant;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración del módulo de Formación dentro de la academia BEKHO.
 *
 * El contenido es genérico de ejemplo (no material ATA oficial), solo para
 * poder mostrar el módulo funcionando.
 */
class FormacionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $academia = Academia::where('nombre', 'BEKHO Power Academy')->first();

        if (! $academia) {
            $this->command?->warn('No existe la academia BEKHO; ejecuta antes RolesPermisosSeeder.');

            return;
        }

        // Fija el tenant activo para que el trait autorelleno de academia_id actúe.
        Tenant::set($academia->id);

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
            $nivel = Nivel::updateOrCreate(
                ['academia_id' => $academia->id, 'nombre' => $datosNivel['nombre']],
                [
                    'descripcion' => $datosNivel['descripcion'],
                    'orden' => $ordenNivel,
                    'activo' => true,
                ],
            );

            foreach ($datosNivel['contenidos'] as $ordenContenido => $datosContenido) {
                Contenido::updateOrCreate(
                    ['nivel_id' => $nivel->id, 'titulo' => $datosContenido['titulo']],
                    [
                        'academia_id' => $academia->id,
                        'tipo' => $datosContenido['tipo'],
                        'cuerpo' => $datosContenido['cuerpo'] ?? null,
                        'url_recurso' => $datosContenido['url_recurso'] ?? null,
                        'orden' => $ordenContenido,
                        'activo' => true,
                    ],
                );
            }
        }

        // Limpia el tenant activo tras sembrar.
        Tenant::olvidar();
    }
}
