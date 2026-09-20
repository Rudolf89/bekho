<?php

namespace Database\Seeders;

use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\TipoRecompensa;
use App\Models\Recompensa;
use Illuminate\Database\Seeder;

/**
 * Catálogo de recompensas (gamificación) de los manuales ATA. Transversal
 * (compartido). Idempotente por (tipo, nombre).
 */
class RecompensasSeeder extends Seeder
{
    public function run(): void
    {
        // --- Franjas de Conocimiento (MAK / Karate for Kids) --------------------
        // 3 franjas negras + amarilla/azul/roja/verde.
        $franjas = [
            ['Franja Negra 1', '#18181b'],
            ['Franja Negra 2', '#18181b'],
            ['Franja Negra 3', '#18181b'],
            ['Franja Amarilla', '#eab308'],
            ['Franja Azul', '#2563eb'],
            ['Franja Roja', '#dc2626'],
            ['Franja Verde', '#16a34a'],
        ];
        foreach ($franjas as $orden => [$nombre, $color]) {
            $this->crear([
                'tipo' => TipoRecompensa::FranjaConocimiento->value,
                'nombre' => $nombre,
                'descripcion' => 'Franja de conocimiento del programa Karate for Kids.',
                'grupo_etario' => GrupoEtario::ForKids->value,
                'color' => $color,
                'emoji' => '🎗️',
                'repetible' => false,
                'orden' => $orden + 1,
            ]);
        }

        // --- Star Tag (Tigers) --------------------------------------------------
        $this->crear([
            'tipo' => TipoRecompensa::StarTag->value,
            'nombre' => 'Estrella Tigre',
            'descripcion' => 'Estrella por una buena acción o logro. Se acumulan a lo largo del programa Tigers.',
            'grupo_etario' => GrupoEtario::Tigers->value,
            'color' => '#f59e0b',
            'emoji' => '⭐',
            'repetible' => true,
            'orden' => 1,
        ]);

        // --- Coleccionables por Habilidad de Vida (todos los grupos) ------------
        $emoji = [
            HabilidadVida::Disciplina->value => '🎯',
            HabilidadVida::Conviccion->value => '🔥',
            HabilidadVida::Comunicacion->value => '💬',
            HabilidadVida::Respeto->value => '🤝',
            HabilidadVida::Autoestima->value => '🌟',
            HabilidadVida::Honestidad->value => '💎',
        ];
        foreach (HabilidadVida::cases() as $orden => $habilidad) {
            $this->crear([
                'tipo' => TipoRecompensa::Coleccionable->value,
                'nombre' => 'Coleccionable: '.$habilidad->etiqueta(),
                'descripcion' => 'Coleccionable de la Habilidad para la Vida "'.$habilidad->etiqueta().'".',
                'habilidad_vida' => $habilidad->value,
                'grupo_etario' => null, // aplica a todos los grupos
                'color' => '#7c3aed',
                'emoji' => $emoji[$habilidad->value] ?? '🏅',
                'repetible' => false,
                'orden' => $orden + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crear(array $datos): void
    {
        Recompensa::updateOrCreate(
            ['tipo' => $datos['tipo'], 'nombre' => $datos['nombre']],
            [...$datos, 'activo' => true],
        );
    }
}
