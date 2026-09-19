<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Enums\PapelEnClase;
use App\Enums\TipoSede;
use App\Models\Asistencia;
use App\Models\CargoRango;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para que el panel/dashboard se vea "vivo".
 *
 * NO es data real: alumnos, clases, pagos y graduaciones de ejemplo dentro de
 * BEKHO. Se puede quitar antes de producción (borrar del DatabaseSeeder).
 */
class DemoBekhoSeeder extends Seeder
{
    public function run(): void
    {
        $grupo = Grupo::where('nombre', 'BEKHO Power Academy')->first();
        if (! $grupo) {
            return;
        }

        Tenant::set($grupo->id);

        $admin = User::sinGrupo()->where('email', 'admin@bekho.cl')->first();

        // Jerarquía: Maestro Rodolfo → admin-plataforma; instructores → Rodolfo.
        $rangoMaestro = CargoRango::where('nombre', 'Maestro')->first();
        $rodolfo = User::updateOrCreate(
            ['email' => 'rodolfo@bekho.cl'],
            [
                'name' => 'Rodolfo González', 'password' => Hash::make('cambiar-esto'),
                'grupo_id' => $grupo->id, 'rango_id' => $rangoMaestro?->id,
                'supervisor_id' => $admin?->id, 'activo' => true,
            ],
        );
        $rodolfo->syncRoles(['direccion']);

        $instructores = collect(['Camila Rojas', 'Diego Soto'])->map(function (string $nombre, int $i) use ($grupo, $rodolfo) {
            $u = User::updateOrCreate(
                ['email' => 'instructor'.($i + 1).'@bekho.cl'],
                [
                    'name' => $nombre, 'password' => Hash::make('cambiar-esto'),
                    'grupo_id' => $grupo->id, 'supervisor_id' => $rodolfo->id, 'activo' => true,
                ],
            );
            $u->syncRoles(['instructor']);

            return $u;
        });

        $sede = Sede::updateOrCreate(
            ['grupo_id' => $grupo->id, 'nombre' => 'BEKHO Central'],
            [
                'comuna' => 'Santiago', 'direccion' => 'Av. Ejemplo 1234',
                'tipo' => TipoSede::Grupo, 'privada' => false, 'activo' => true,
            ],
        );

        // Los instructores (y Rodolfo) quedan asignados a la sede: así aparecen
        // como instructores disponibles al inscribir un alumno en esa sede.
        $sede->instructores()->sync(
            $instructores->pluck('id')->push($rodolfo->id)->all(),
        );

        // Alumnos por grupo etario. El nivel se deriva del cinturón, así que se
        // les asigna un grado de cada banda para que la demo muestre variedad.
        $gruposEtarios = ['tigers', 'for_kids', 'jovenes_adultos'];
        $bandasColores = [
            ['Blanco', 'Naranjo', 'Amarillo'],   // -> Principiantes
            ['Camuflado', 'Verde', 'Púrpura'],   // -> Intermedio
            ['Azul', 'Café', 'Rojo'],            // -> Avanzado
        ];
        $nombres = ['Antonia', 'Benjamín', 'Catalina', 'Diego', 'Emilia', 'Felipe', 'Gabriela', 'Hugo',
            'Isidora', 'Joaquín', 'Karla', 'Lucas', 'Martina', 'Nicolás', 'Olivia', 'Pablo',
            'Renata', 'Sebastián', 'Tamara', 'Vicente', 'Ximena', 'Agustín', 'Florencia', 'Matías'];

        foreach ($nombres as $i => $nombre) {
            $grupoEtario = $gruposEtarios[$i % 3];
            $escala = EscalaGrado::paraGrupo(GrupoEtario::from($grupoEtario));
            $grado = Grado::porEscala($escala)
                ->whereIn('color', $bandasColores[$i % 3])
                ->ordenados()
                ->first();

            Estudiante::updateOrCreate(
                ['grupo_id' => $grupo->id, 'nombre' => $nombre.' '.['Pérez', 'Soto', 'Muñoz', 'Rojas'][$i % 4]],
                [
                    'sede_id' => $sede->id,
                    'grupo_etario' => $grupoEtario,
                    'grado_id' => $grado?->id,
                    // El nivel se deriva del cinturón. En la semilla los eventos de
                    // modelo están apagados (WithoutModelEvents), así que se calcula
                    // aquí con el mismo criterio que usa el modelo.
                    'nivel' => $grado?->nivelEntrenamiento() ?? NivelEntrenamiento::Principiantes,
                    'fecha_nacimiento' => now()->subYears(6 + $i % 25),
                    'activo' => true,
                    'created_at' => $i < 6 ? now()->subDays($i) : now()->subMonths(3),
                ],
            );
        }

        // Clases de HOY (una por grupo etario), enlazadas a la planilla
        // transversal (grupo × Principiantes) que siembra el PlanificadorSeeder.
        $diaHoy = (int) now()->dayOfWeekIso;
        $config = [
            ['tigers', '16:00', '16:45', $instructores[0]],
            ['for_kids', '17:00', '18:15', $instructores[1]],
            ['jovenes_adultos', '18:30', '19:15', $rodolfo],
        ];

        foreach ($config as [$grupoEtario, $ini, $fin, $instructor]) {
            $planilla = Planilla::where('grupo_etario', $grupoEtario)->where('nivel', 'principiantes')->first();

            $clase = Clase::updateOrCreate(
                ['grupo_id' => $grupo->id, 'nombre' => 'Clase '.$grupoEtario],
                [
                    'sede_id' => $sede->id, 'planilla_id' => $planilla?->id,
                    'grupo_etario' => $grupoEtario, 'activo' => true,
                ],
            );

            // La clase se reúne hoy (una sola sesión en la demo).
            $clase->horarios()->updateOrCreate(
                ['dia_semana' => $diaHoy, 'hora_inicio' => $ini],
                ['hora_fin' => $fin],
            );

            // El instructor configurado queda como titular; Rodolfo apoya como
            // asistente (demuestra clases con varios instructores).
            $papeles = [$instructor->id => PapelEnClase::Titular->value];
            if (! $instructor->is($rodolfo)) {
                $papeles[$rodolfo->id] = PapelEnClase::Asistente->value;
            }
            $clase->sincronizarInstructores($papeles);
        }

        // La asistencia de demostración se siembra en DemoAsistenciaSeeder, que
        // corre después de MigraPersonasSeeder (la asistencia va por matrícula).

        // Pagos, convocatoria de examen y graduaciones de demostración se siembran
        // en DemoPagosSeeder y DemoExamenesSeeder (van por matrícula, que se crea
        // después en MigraPersonasSeeder).

        Tenant::olvidar();
    }
}
