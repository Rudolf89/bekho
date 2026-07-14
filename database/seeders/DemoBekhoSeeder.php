<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Enums\EstadoAsistencia;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Enums\PapelEnClase;
use App\Enums\TipoPago;
use App\Enums\TipoSede;
use App\Models\Academia;
use App\Models\Asistencia;
use App\Models\CargoRango;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Graduacion;
use App\Models\Pago;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
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
        $academia = Academia::where('nombre', 'BEKHO Power Academy')->first();
        if (! $academia) {
            return;
        }

        Tenant::set($academia->id);

        $admin = User::sinAcademia()->where('email', 'admin@bekho.cl')->first();

        // Jerarquía: Maestro Rodolfo → admin-plataforma; instructores → Rodolfo.
        $rangoMaestro = CargoRango::where('nombre', 'Maestro')->first();
        $rodolfo = User::updateOrCreate(
            ['email' => 'rodolfo@bekho.cl'],
            [
                'name' => 'Rodolfo González', 'password' => Hash::make('cambiar-esto'),
                'academia_id' => $academia->id, 'rango_id' => $rangoMaestro?->id,
                'supervisor_id' => $admin?->id, 'activo' => true,
            ],
        );
        $rodolfo->syncRoles(['direccion']);

        $instructores = collect(['Camila Rojas', 'Diego Soto'])->map(function (string $nombre, int $i) use ($academia, $rodolfo) {
            $u = User::updateOrCreate(
                ['email' => 'instructor'.($i + 1).'@bekho.cl'],
                [
                    'name' => $nombre, 'password' => Hash::make('cambiar-esto'),
                    'academia_id' => $academia->id, 'supervisor_id' => $rodolfo->id, 'activo' => true,
                ],
            );
            $u->syncRoles(['instructor']);

            return $u;
        });

        $sede = Sede::updateOrCreate(
            ['academia_id' => $academia->id, 'nombre' => 'BEKHO Central'],
            [
                'comuna' => 'Santiago', 'direccion' => 'Av. Ejemplo 1234',
                'tipo' => TipoSede::Academia, 'privada' => false, 'activo' => true,
            ],
        );

        // Alumnos por grupo etario. El nivel se deriva del cinturón, así que se
        // les asigna un grado de cada banda para que la demo muestre variedad.
        $grupos = ['tigers', 'for_kids', 'jovenes_adultos'];
        $bandasColores = [
            ['Blanco', 'Naranjo', 'Amarillo'],   // -> Principiantes
            ['Camuflado', 'Verde', 'Púrpura'],   // -> Intermedio
            ['Azul', 'Café', 'Rojo'],            // -> Avanzado
        ];
        $nombres = ['Antonia', 'Benjamín', 'Catalina', 'Diego', 'Emilia', 'Felipe', 'Gabriela', 'Hugo',
            'Isidora', 'Joaquín', 'Karla', 'Lucas', 'Martina', 'Nicolás', 'Olivia', 'Pablo',
            'Renata', 'Sebastián', 'Tamara', 'Vicente', 'Ximena', 'Agustín', 'Florencia', 'Matías'];

        foreach ($nombres as $i => $nombre) {
            $grupo = $grupos[$i % 3];
            $escala = EscalaGrado::paraGrupo(GrupoEtario::from($grupo));
            $grado = Grado::porEscala($escala)
                ->whereIn('color', $bandasColores[$i % 3])
                ->ordenados()
                ->first();

            Estudiante::updateOrCreate(
                ['academia_id' => $academia->id, 'nombre' => $nombre.' '.['Pérez', 'Soto', 'Muñoz', 'Rojas'][$i % 4]],
                [
                    'sede_id' => $sede->id,
                    'grupo_etario' => $grupo,
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

        // Planillas + clases de HOY (una por grupo etario).
        $diaHoy = (int) now()->dayOfWeekIso;
        $config = [
            ['tigers', '16:00', '16:45', 'disciplina', $instructores[0]],
            ['for_kids', '17:00', '18:15', 'respeto', $instructores[1]],
            ['jovenes_adultos', '18:30', '19:15', 'honestidad', $rodolfo],
        ];

        foreach ($config as [$grupo, $ini, $fin, $habilidad, $instructor]) {
            $planilla = Planilla::updateOrCreate(
                ['academia_id' => $academia->id, 'nombre' => 'Rutina '.$grupo],
                ['grupo_etario' => $grupo, 'nivel' => 'principiantes', 'habilidad_vida' => $habilidad, 'activo' => true],
            );
            if ($planilla->bloques()->count() === 0) {
                $planilla->generarEstructura();
            }

            $clase = Clase::updateOrCreate(
                ['academia_id' => $academia->id, 'nombre' => 'Clase '.$grupo, 'dia_semana' => $diaHoy],
                [
                    'sede_id' => $sede->id, 'planilla_id' => $planilla->id,
                    'grupo_etario' => $grupo, 'hora_inicio' => $ini, 'hora_fin' => $fin, 'activo' => true,
                ],
            );

            // El instructor configurado queda como titular; Rodolfo apoya como
            // asistente (demuestra clases con varios instructores).
            $papeles = [$instructor->id => PapelEnClase::Titular->value];
            if (! $instructor->is($rodolfo)) {
                $papeles[$rodolfo->id] = PapelEnClase::Asistente->value;
            }
            $clase->sincronizarInstructores($papeles);
        }

        // Asistencia de hoy (marca presentes a la mitad).
        $clasesHoy = Clase::where('dia_semana', $diaHoy)->get();
        foreach ($clasesHoy as $clase) {
            foreach ($clase->estudiantesEsperados()->get() as $j => $est) {
                Asistencia::updateOrCreate(
                    ['clase_id' => $clase->id, 'estudiante_id' => $est->id, 'fecha' => now()->toDateString()],
                    ['academia_id' => $academia->id, 'estado' => $j % 2 === 0 ? EstadoAsistencia::Presente : EstadoAsistencia::Ausente],
                );
            }
        }

        // Pagos del mes (dos tercios pagan; el resto queda moroso).
        $alumnos = Estudiante::activos()->get();
        foreach ($alumnos as $k => $est) {
            if ($k % 3 !== 0) {
                Pago::updateOrCreate(
                    ['estudiante_id' => $est->id, 'tipo' => TipoPago::Mensualidad->value, 'periodo' => now()->startOfMonth()->toDateString()],
                    ['academia_id' => $academia->id, 'monto' => 35000, 'fecha_pago' => now()->toDateString()],
                );
            }
        }

        // Convocatoria próxima con inscritos.
        $conv = Convocatoria::updateOrCreate(
            ['academia_id' => $academia->id, 'nombre' => 'Examen de grado'],
            ['sede_id' => $sede->id, 'fecha' => now()->addDays(9)->toDateString(), 'estado' => 'programada'],
        );
        foreach ($alumnos->take(9) as $est) {
            $conv->inscripciones()->updateOrCreate(
                ['estudiante_id' => $est->id],
                ['academia_id' => $academia->id, 'grado_origen_id' => $est->grado_id, 'instructor_id' => $rodolfo->id],
            );
        }

        // Historial de graduaciones (conteo en cascada del maestro/admin-plataforma).
        $grado = Grado::porEscala(EscalaGrado::Adultos)->ordenados()->first();
        foreach ($alumnos->take(14) as $m => $est) {
            $instructor = $instructores[$m % 2];
            Graduacion::updateOrCreate(
                ['academia_id' => $academia->id, 'estudiante_id' => $est->id, 'convocatoria_id' => null, 'instructor_id' => $instructor->id],
                ['grado_destino_id' => $grado?->id, 'fecha' => now()->subMonths($m % 6 + 1)->toDateString(), 'resultado' => 'aprobado'],
            );
        }

        Tenant::olvidar();
    }
}
