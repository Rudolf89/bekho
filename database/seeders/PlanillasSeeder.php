<?php

namespace Database\Seeders;

use App\Enums\EstadoPlanilla;
use App\Models\Federacion;
use App\Models\Planilla;
use Illuminate\Database\Seeder;

/**
 * Planillas imprimibles del programa.
 *
 * PROCEDENCIA: el listado viene del prototipo de pantallas, NO de un documento
 * de la federación, así que va con `verificado = false` y la pantalla lo avisa.
 * Las columnas se transcriben tal cual del prototipo, sin agregar ninguna: donde
 * el prototipo describía la planilla en vez de enumerar sus casillas, la planilla
 * queda SIN columnas y la escuela las define desde el editor. No se inventa el
 * contenido de un formulario que después se imprime y se usa en la cancha.
 */
class PlanillasSeeder extends Seeder
{
    private const FUENTE = 'Prototipo de pantallas BEKHO (pendiente de confirmar con la escuela)';

    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();

        if (! $federacion) {
            return;
        }

        foreach ($this->planillas() as $orden => $datos) {
            $planilla = Planilla::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $datos['nombre']],
                [
                    'uso' => $datos['uso'],
                    'descripcion' => $datos['descripcion'],
                    'estado' => $datos['estado']->value,
                    'version' => $datos['version'],
                    'activo' => true,
                    'orden' => $orden + 1,
                    'fuente' => self::FUENTE,
                    'verificado' => false,
                ],
            );

            foreach ($datos['columnas'] as $ordenColumna => $titulo) {
                $planilla->columnas()->updateOrCreate(
                    ['titulo' => $titulo],
                    ['orden' => $ordenColumna + 1],
                );
            }
        }
    }

    /**
     * @return list<array{nombre: string, uso: string, descripcion: string, estado: EstadoPlanilla, version: int, columnas: list<string>}>
     */
    private function planillas(): array
    {
        return [
            [
                'nombre' => 'Planilla de examen de grado',
                'uso' => 'Examen del ciclo',
                'descripcion' => 'Forma, técnica, defensa, teoría, asistencia',
                'estado' => EstadoPlanilla::Vigente,
                'version' => 4,
                'columnas' => ['Forma', 'Técnica', 'Defensa', 'Teoría', 'Asistencia'],
            ],
            [
                'nombre' => 'Checklist técnico por requisito',
                'uso' => 'Carga del instructor',
                'descripcion' => '6 requisitos por grado, con nota y fecha',
                'estado' => EstadoPlanilla::Vigente,
                'version' => 3,
                // El prototipo la describe, no enumera sus casillas: las define la escuela.
                'columnas' => [],
            ],
            [
                'nombre' => 'Planilla de arbitraje — fórmula',
                'uso' => 'Torneo',
                'descripcion' => 'Puntaje por juez, desempate, firma del Central',
                'estado' => EstadoPlanilla::Vigente,
                'version' => 2,
                'columnas' => ['Puntaje por juez', 'Desempate', 'Firma del Central'],
            ],
            [
                'nombre' => 'Planilla de arbitraje — sparring',
                'uso' => 'Torneo',
                'descripcion' => 'Puntos, advertencias, tiempo, resultado',
                'estado' => EstadoPlanilla::Vigente,
                'version' => 2,
                'columnas' => ['Puntos', 'Advertencias', 'Tiempo', 'Resultado'],
            ],
            [
                'nombre' => 'Registro de lección de vida',
                'uso' => 'Ciclo',
                'descripcion' => '5 días, acción diaria, firma del tutor',
                'estado' => EstadoPlanilla::Vigente,
                'version' => 1,
                'columnas' => ['5 días', 'Acción diaria', 'Firma del tutor'],
            ],
            [
                'nombre' => 'Planilla de evaluación Legacy',
                'uso' => 'Certificación',
                'descripcion' => 'Clases dirigidas, observaciones del supervisor',
                'estado' => EstadoPlanilla::Borrador,
                'version' => 1,
                'columnas' => ['Clases dirigidas', 'Observaciones del supervisor'],
            ],
        ];
    }
}
