<?php

namespace Database\Seeders;

use App\Enums\TipoSede;
use App\Models\Academia;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Siembra las ACADEMIAS (grupos) de la federación BEKHO y sus sedes conocidas, y
 * enlaza cada academia con su "campo" de la línea de supervisión cuando hay base
 * para hacerlo.
 *
 * ⚠️ DATOS NO CONFIRMADOS POR LA FEDERACIÓN. Provienen de investigación sobre
 * fragmentos públicos (sitios de cada academia) — ver `docs/linea-supervision-
 * academias.md` para el nivel de confianza de cada relación y las inconsistencias
 * abiertas. Es un punto de partida a validar con la escuela, no la verdad oficial.
 *
 * El enlace academia ↔ campo solo se aplica a los campos con base suficiente
 * (confianza alta): a los usuarios de ese campo (supervisor + miembros) se les
 * fija `academia_id`. El resto de la línea sigue a nivel federación (null); el
 * conteo en cascada del collar no se ve afectado (usa `sinAcademia()`).
 *
 * Debe correr DESPUÉS de LineaSupervisionSeeder (necesita los usuarios ya creados).
 * Seeder idempotente (updateOrCreate por nombre).
 */
class AcademiasBekhoSeeder extends Seeder
{
    public function run(): void
    {
        $rutaAcademias = database_path('data/academias_bekho.json');
        $rutaLinea = database_path('data/linea_supervision.json');

        if (! is_file($rutaAcademias)) {
            return;
        }

        /** @var list<array<string, mixed>> $academias */
        $academias = json_decode((string) file_get_contents($rutaAcademias), true);

        // Campo → nombres de sus integrantes (supervisor + miembros), para enlazar.
        $integrantesPorCampo = $this->integrantesPorCampo($rutaLinea);

        // Mapa nombre normalizado → usuario (una sola pasada).
        $usuariosPorNombre = User::sinAcademia()->get()
            ->keyBy(fn (User $u) => $this->clave($u->name));

        foreach ($academias as $datos) {
            $academia = Academia::updateOrCreate(
                ['nombre' => $datos['nombre']],
                ['activo' => true],
            );

            foreach ($datos['sedes'] as $sede) {
                Sede::updateOrCreate(
                    ['academia_id' => $academia->id, 'nombre' => $sede['nombre']],
                    [
                        'comuna' => $sede['comuna'] ?? null,
                        'direccion' => $sede['direccion'] ?? null,
                        'tipo' => TipoSede::Academia,
                        'privada' => false,
                        'activo' => true,
                    ],
                );
            }

            // Enlace academia ↔ campo (solo si el JSON trae un campo con base).
            $campo = $datos['campo'] ?? null;
            if ($campo === null) {
                continue;
            }

            foreach ($integrantesPorCampo[$campo] ?? [] as $nombre) {
                $usuario = $usuariosPorNombre->get($this->clave($nombre));
                $usuario?->update(['academia_id' => $academia->id]);
            }
        }
    }

    /**
     * Lee la línea de supervisión y devuelve, por número de campo, la lista de
     * nombres (supervisor + miembros).
     *
     * @return array<int, list<string>>
     */
    private function integrantesPorCampo(string $ruta): array
    {
        if (! is_file($ruta)) {
            return [];
        }

        /** @var list<array{campo: int, supervisor: string, miembros: list<string>}> $campos */
        $campos = json_decode((string) file_get_contents($ruta), true);

        $mapa = [];
        foreach ($campos as $campo) {
            $mapa[$campo['campo']] = array_merge([$campo['supervisor']], $campo['miembros']);
        }

        return $mapa;
    }

    /** Clave de comparación por nombre: sin acentos, minúsculas, espacios normalizados. */
    private function clave(string $nombre): string
    {
        return Str::of($nombre)->ascii()->lower()->squish()->value();
    }
}
