<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Enums\EstadoMatricula;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Enums\PapelEnClase;
use App\Enums\TipoSede;
use App\Models\CargoRango;
use App\Models\Clase;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Instructor;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\PlanificacionClase;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para que el panel/dashboard se vea "vivo".
 *
 * NO es data real: personal, alumnos, clases, pagos y graduaciones de ejemplo
 * dentro de BEKHO. Crea directamente la capa de identidad del rediseño
 * (personas + matrículas + personal_grupo + instructores); ya no hay tabla
 * estudiantes. Se puede quitar antes de producción (borrar del DatabaseSeeder).
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

        // Rango Instructor: así los instructores obtienen su faceta de instructor
        // (y la cadena de supervisión) para el crédito de graduación.
        $rangoInstructor = CargoRango::where('nombre', 'Instructor')->first();
        $instructores = collect(['Camila Rojas', 'Diego Soto'])->map(function (string $nombre, int $i) use ($grupo, $rodolfo, $rangoInstructor) {
            $u = User::updateOrCreate(
                ['email' => 'instructor'.($i + 1).'@bekho.cl'],
                [
                    'name' => $nombre, 'password' => Hash::make('cambiar-esto'),
                    'grupo_id' => $grupo->id, 'rango_id' => $rangoInstructor?->id,
                    'supervisor_id' => $rodolfo->id, 'activo' => true,
                ],
            );
            $u->syncRoles(['instructor']);

            return $u;
        });

        // Identidad del personal: cada usuario obtiene su persona + vínculo de
        // personal y, si tiene rango, su registro de instructor. Idempotente por
        // guarda (el usuario ya con persona no se vuelve a derivar).
        $this->derivarIdentidadPersonal();

        // Recargar los usuarios en memoria para tener su persona_id recién creado.
        $rodolfo->refresh();
        $instructores->each->refresh();

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

        // Instructor a cargo por grupo etario (persona del instructor).
        $instructorPorEtario = [
            'tigers' => $instructores[0]->persona_id,
            'for_kids' => $instructores[1]->persona_id,
            'jovenes_adultos' => $rodolfo->persona_id,
        ];

        // Alumnos (persona + matrícula). El nivel se deriva del cinturón, así que
        // se les asigna un grado de cada banda para que la demo muestre variedad.
        $gruposEtarios = ['tigers', 'for_kids', 'jovenes_adultos'];
        $bandasColores = [
            ['Blanco', 'Naranjo', 'Amarillo'],   // -> Principiantes
            ['Camuflado', 'Verde', 'Púrpura'],   // -> Intermedio
            ['Azul', 'Café', 'Rojo'],            // -> Avanzado
        ];
        $nombres = ['Antonia', 'Benjamín', 'Catalina', 'Diego', 'Emilia', 'Felipe', 'Gabriela', 'Hugo',
            'Isidora', 'Joaquín', 'Karla', 'Lucas', 'Martina', 'Nicolás', 'Olivia', 'Pablo',
            'Renata', 'Sebastián', 'Tamara', 'Vicente', 'Ximena', 'Agustín', 'Florencia', 'Matías'];
        $apellidos = ['Pérez', 'Soto', 'Muñoz', 'Rojas'];

        // Idempotencia: si el grupo ya tiene matrículas de demo, no recrear.
        if (! Matricula::withoutGlobalScopes()->where('grupo_id', $grupo->id)->exists()) {
            foreach ($nombres as $i => $nombre) {
                $grupoEtario = $gruposEtarios[$i % 3];
                $escala = EscalaGrado::paraGrupo(GrupoEtario::from($grupoEtario));
                $grado = Grado::porEscala($escala)
                    ->whereIn('color', $bandasColores[$i % 3])
                    ->ordenados()
                    ->first();

                $persona = Persona::create([
                    'nombres' => $nombre,
                    'apellido_paterno' => $apellidos[$i % 4],
                    'fecha_nacimiento' => now()->subYears(6 + $i % 25),
                    'grado_id' => $grado?->id, // caché del último grado
                ]);

                Matricula::create([
                    'grupo_id' => $grupo->id,
                    'persona_id' => $persona->id,
                    'sede_id' => $sede->id,
                    'grupo_etario' => $grupoEtario,
                    // El nivel se deriva del cinturón. En la semilla los eventos de
                    // modelo están apagados (WithoutModelEvents), así que se calcula
                    // aquí con el mismo criterio que usa el catálogo de grados.
                    'nivel' => $grado?->nivelEntrenamiento()->value ?? NivelEntrenamiento::Principiantes->value,
                    'estado' => EstadoMatricula::Activa->value,
                    'fecha_ingreso' => $i < 6 ? now()->subDays($i) : now()->subMonths(3),
                    'instructor_persona_id' => $instructorPorEtario[$grupoEtario] ?? null,
                    'created_at' => $i < 6 ? now()->subDays($i) : now()->subMonths(3),
                ]);
            }
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
            $planificacion = PlanificacionClase::where('grupo_etario', $grupoEtario)->where('nivel', 'principiantes')->first();

            $clase = Clase::updateOrCreate(
                ['grupo_id' => $grupo->id, 'nombre' => 'Clase '.$grupoEtario],
                [
                    'sede_id' => $sede->id, 'planificacion_clase_id' => $planificacion?->id,
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

        // La asistencia, pagos, convocatoria y graduaciones de demostración se
        // siembran en DemoAsistenciaSeeder/DemoPagosSeeder/DemoExamenesSeeder,
        // que corren después y operan por matrícula.

        Tenant::olvidar();
    }

    /**
     * Deriva la capa de identidad del personal desde los usuarios: persona por
     * usuario, personal_grupo (si tiene grupo), instructor (si tiene rango) y el
     * árbol de supervisión (persona → persona). Idempotente: salta los usuarios
     * que ya tienen persona. Las fechas de nacimiento son de DEMO.
     */
    protected function derivarIdentidadPersonal(): void
    {
        foreach (User::sinGrupo()->whereNull('persona_id')->get() as $user) {
            $persona = $this->personaDesdeNombre($user->name, [
                'email' => $user->email,
                'telefono' => $user->telefono,
                'fecha_nacimiento' => now()->subYears(30)->toDateString(), // demo
            ]);
            $user->forceFill(['persona_id' => $persona->id])->save();

            if ($user->grupo_id) {
                $personal = PersonalGrupo::updateOrCreate(
                    ['persona_id' => $persona->id, 'grupo_id' => $user->grupo_id],
                    ['activo' => $user->activo],
                );

                // Refleja los roles del usuario como roles del personal en el grupo
                // (sin sede acotada por ahora).
                foreach ($user->roles as $role) {
                    $personal->otorgarRol($role);
                }
            }

            if ($user->rango_id) {
                Instructor::updateOrCreate(
                    ['persona_id' => $persona->id],
                    ['rango_id' => $user->rango_id],
                );
            }
        }

        // Árbol de supervisión entre instructores (persona → persona).
        foreach (User::sinGrupo()->whereNotNull('supervisor_id')->whereNotNull('persona_id')->get() as $user) {
            $supervisorPersonaId = User::sinGrupo()->find($user->supervisor_id)?->persona_id;
            if ($supervisorPersonaId) {
                Instructor::where('persona_id', $user->persona_id)
                    ->update(['supervisor_persona_id' => $supervisorPersonaId]);
            }
        }
    }

    /**
     * Crea una persona repartiendo el nombre completo en nombres / apellidos.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function personaDesdeNombre(string $nombreCompleto, array $extra): Persona
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto)) ?: [];
        $nombres = array_shift($partes) ?: $nombreCompleto;
        $apellidoPaterno = array_shift($partes);
        $apellidoMaterno = $partes !== [] ? implode(' ', $partes) : null;

        return Persona::create(array_merge([
            'nombres' => $nombres,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
        ], $extra));
    }
}
