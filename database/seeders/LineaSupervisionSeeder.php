<?php

namespace Database\Seeders;

use App\Models\CargoRango;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siembra la LÍNEA DE SUPERVISIÓN real de la federación BEKHO: los 19 "campos"
 * (cada uno con su supervisor) y sus instructores a cargo, encadenados por
 * `users.supervisor_id`. Este árbol es el eje del conteo en cascada del collar
 * de máster (ver App\Services\ServicioExamenes::lineaDescendente).
 *
 * Decisiones de modelado (documentadas y corregibles):
 *  - Los usuarios son de la federación → `academia_id = null` (la cascada los
 *    recorre con `sinAcademia()`).
 *  - Todos reciben el rol spatie `instructor` (la escuela confirmó "todos son
 *    instructores"); el ROL da permisos, el RANGO es aparte.
 *  - El RANGO (cargos_rangos) se deduce del tratamiento marcial del nombre:
 *    CHIEF MASTER → Maestro Jefe, SENIOR MASTER → Maestro Sénior, MASTER →
 *    Maestro. SR./SRTA. no fija rango (solo rol instructor).
 *  - Email determinista `nombre.apellido@bekho.local` (placeholder: no hay
 *    correos reales todavía); si dos personas colisionan se numera el slug.
 *  - Una persona que aparece en más de un campo se crea una sola vez: gana la
 *    PRIMERA aparición (orden del archivo).
 *
 * Fuente: database/data/linea_supervision.json (árbol validado con la escuela).
 * Seeder idempotente (updateOrCreate por email).
 */
class LineaSupervisionSeeder extends Seeder
{
    /** Tratamientos marciales → nombre del CargoRango (los demás no fijan rango). */
    private const RANGOS = [
        'CHIEF MASTER' => 'Maestro Jefe',
        'SENIOR MASTER' => 'Maestro Sénior',
        'MASTER' => 'Maestro',
    ];

    /** Tratamientos a quitar del nombre para derivar el slug de correo (más largo primero). */
    private const TRATAMIENTOS = ['CHIEF MASTER', 'SENIOR MASTER', 'MASTER', 'SRTA.', 'SR.', 'SRTA', 'SR'];

    /**
     * Nombres normalizados ya creados → id de usuario (dedupe primera-aparición-gana).
     *
     * @var array<string, int>
     */
    private array $creados = [];

    /**
     * Slugs de correo ya usados → cuántas veces (para desambiguar colisiones).
     *
     * @var array<string, int>
     */
    private array $slugs = [];

    public function run(): void
    {
        $ruta = database_path('data/linea_supervision.json');

        if (! is_file($ruta)) {
            return;
        }

        /** @var list<array{campo: int, supervisor: string, miembros: list<string>}> $campos */
        $campos = json_decode((string) file_get_contents($ruta), true);

        $rangos = CargoRango::pluck('id', 'nombre');
        $instructor = 'instructor';

        // 1) Los 19 supervisores (raíces del árbol): supervisor_id = null.
        foreach ($campos as $campo) {
            $sup = $this->crearUsuario($campo['supervisor'], null, $rangos);
            $sup->syncRoles([$instructor]);
        }

        // 2) Los miembros de cada campo cuelgan de su supervisor.
        foreach ($campos as $campo) {
            $supId = $this->creados[$this->clave($campo['supervisor'])];

            foreach ($campo['miembros'] as $nombre) {
                $miembro = $this->crearUsuario($nombre, $supId, $rangos);
                $miembro->syncRoles([$instructor]);
            }
        }
    }

    /**
     * Crea (o recupera) el usuario para un nombre con tratamiento. Dedupe por
     * nombre normalizado: la primera aparición gana y las siguientes solo
     * devuelven el usuario ya creado (sin reasignar supervisor).
     *
     * @param  Collection<string, int>  $rangos
     */
    private function crearUsuario(string $nombre, ?int $supervisorId, $rangos): User
    {
        $clave = $this->clave($nombre);

        if (isset($this->creados[$clave])) {
            return User::sinAcademia()->find($this->creados[$clave]);
        }

        $user = User::updateOrCreate(
            ['email' => $this->email($nombre)],
            [
                'name' => $this->nombreLimpio($nombre),
                'password' => Hash::make(Str::random(32)),
                'academia_id' => null,
                'rango_id' => $this->rangoId($nombre, $rangos),
                'supervisor_id' => $supervisorId,
                'activo' => true,
            ],
        );

        $this->creados[$clave] = $user->id;

        return $user;
    }

    /** Clave de dedupe: nombre completo (con tratamiento) sin acentos, minúsculas. */
    private function clave(string $nombre): string
    {
        return Str::of($nombre)->ascii()->lower()->squish()->value();
    }

    /** Rango deducido del tratamiento marcial, o null si es SR./SRTA. */
    private function rangoId(string $nombre, $rangos): ?int
    {
        $upper = Str::upper($nombre);

        foreach (self::RANGOS as $tratamiento => $cargo) {
            if (Str::startsWith($upper, $tratamiento.' ')) {
                return $rangos[$cargo] ?? null;
            }
        }

        return null;
    }

    /**
     * Nombre a guardar: se conserva el tratamiento completo (SR./MASTER/…) tal
     * como lo entregó la escuela — es significativo en el contexto marcial y
     * evita ambigüedades entre homónimos.
     */
    private function nombreLimpio(string $nombre): string
    {
        return Str::of($nombre)->squish()->value();
    }

    /** Correo determinista a partir del nombre personal; desambigua colisiones. */
    private function email(string $nombre): string
    {
        $base = Str::of($this->sinTratamiento($nombre))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        $base = $base !== '' ? $base : 'instructor';

        $n = ($this->slugs[$base] ?? 0) + 1;
        $this->slugs[$base] = $n;

        return $n === 1 ? "{$base}@bekho.local" : "{$base}{$n}@bekho.local";
    }

    /** Devuelve el nombre sin el tratamiento marcial/honorífico inicial. */
    private function sinTratamiento(string $nombre): string
    {
        $limpio = Str::of($nombre)->squish();
        $upper = Str::upper($limpio->value());

        foreach (self::TRATAMIENTOS as $tratamiento) {
            if (Str::startsWith($upper, $tratamiento.' ')) {
                return $limpio->after($tratamiento.' ')->squish()->value();
            }
        }

        return $limpio->value();
    }
}
