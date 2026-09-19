<?php

namespace Database\Seeders;

use App\Enums\EstadoMatricula;
use App\Models\Estudiante;
use App\Models\Instructor;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\Tutela;
use App\Models\User;
use App\Support\Rut;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rediseño Fase 2 (migración de datos): deriva la capa de identidad nueva
 * (personas, matriculas, tutelas, personal_grupo, instructores) desde la
 * operación actual (users, estudiantes, apoderado_estudiante). Se ejecuta
 * después de DemoBekhoSeeder. Es aditivo: no toca las tablas de origen, que
 * seguirán mandando en la operación hasta el recableo de la Fase 4.
 *
 * Idempotente por guarda: si los usuarios ya tienen persona, no hace nada.
 * Las fechas de nacimiento que falten en el origen (p. ej. del personal) se
 * completan con un valor de DEMO, porque esta migración parte de datos de
 * demostración; la migración real usará los datos verdaderos de la escuela.
 */
class MigraPersonasSeeder extends Seeder
{
    public function run(): void
    {
        if (User::whereNotNull('persona_id')->exists()) {
            return; // ya migrado
        }

        // 1) Personal: cada usuario obtiene su persona + vínculo de personal y,
        //    si tiene rango, su registro de instructor.
        foreach (User::all() as $user) {
            $persona = $this->personaDesdeNombre($user->name, [
                'email' => $user->email,
                'telefono' => $user->telefono,
                'fecha_nacimiento' => now()->subYears(30)->toDateString(), // demo
            ]);
            $user->forceFill(['persona_id' => $persona->id])->save();

            if ($user->grupo_id) {
                PersonalGrupo::updateOrCreate(
                    ['persona_id' => $persona->id, 'grupo_id' => $user->grupo_id],
                    ['activo' => $user->activo],
                );
            }

            if ($user->rango_id) {
                Instructor::updateOrCreate(
                    ['persona_id' => $persona->id],
                    ['rango_id' => $user->rango_id],
                );
            }
        }

        // 1b) Árbol de supervisión entre instructores (persona → persona).
        foreach (User::whereNotNull('supervisor_id')->whereNotNull('persona_id')->get() as $user) {
            $supervisorPersonaId = User::find($user->supervisor_id)?->persona_id;
            if ($supervisorPersonaId) {
                Instructor::where('persona_id', $user->persona_id)
                    ->update(['supervisor_persona_id' => $supervisorPersonaId]);
            }
        }

        // 2) Alumnos: cada estudiante genera su persona + matrícula.
        $personaPorEstudiante = [];
        foreach (Estudiante::withoutGlobalScopes()->get() as $est) {
            $persona = $this->personaDesdeNombre($est->nombre, [
                'fecha_nacimiento' => $est->fecha_nacimiento?->toDateString() ?? now()->subYears(12)->toDateString(),
                'telefono' => $est->telefono_contacto,
                'email' => $est->email_contacto,
                'direccion' => $est->direccion,
                'comuna' => $est->comuna,
                'region' => $est->region,
                'grado_id' => $est->grado_id,
            ]);
            $personaPorEstudiante[$est->id] = $persona->id;

            if ($est->rut) {
                $persona->documentos()->create([
                    'tipo' => 'rut',
                    'numero' => Rut::normalizar($est->rut) ?? $est->rut,
                    'pais' => 'CL',
                    'principal' => true,
                ]);
            }

            Matricula::withoutGlobalScopes()->create([
                'persona_id' => $persona->id,
                'grupo_id' => $est->grupo_id,
                'sede_id' => $est->sede_id,
                'estado' => $est->activo ? EstadoMatricula::Activa->value : EstadoMatricula::Retirada->value,
                'fecha_ingreso' => $est->created_at?->toDateString(),
                'instructor_persona_id' => $est->instructor_id ? User::find($est->instructor_id)?->persona_id : null,
                'dia_vencimiento' => $est->dia_vencimiento,
                'acepto_reglamento_at' => $est->acepto_reglamento_at,
            ]);
        }

        // 3) Tutelas desde el pivote apoderado_estudiante (si lo hubiera).
        foreach (DB::table('apoderado_estudiante')->get() as $row) {
            $apoderadoPersonaId = User::find($row->user_id)?->persona_id;
            $alumnoPersonaId = $personaPorEstudiante[$row->estudiante_id] ?? null;

            if ($apoderadoPersonaId && $alumnoPersonaId) {
                Tutela::updateOrCreate(
                    ['apoderado_persona_id' => $apoderadoPersonaId, 'alumno_persona_id' => $alumnoPersonaId],
                    ['parentesco' => 'otro', 'responsable_pago' => true],
                );
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
