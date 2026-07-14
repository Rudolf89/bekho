<?php

namespace Database\Seeders;

use App\Models\Academia;
use App\Models\ConfiguracionPago;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Siembra permisos, roles y el administrador de plataforma.
 *
 * Jerarquía real: BEKHO es la FEDERACIÓN (la plataforma), NO una academia. Cada
 * "academia" del sistema es un GRUPO (p. ej. BEKHO Power Academy). El primer
 * grupo se crea aquí; el resto (Pride, IV Región, Strike, …) aún no se confirman.
 *
 * Rol (spatie) = permisos en el software. Es un eje INDEPENDIENTE del rango
 * marcial (cargos_rangos → users.rango_id), que es "quién eres" y no da permisos.
 */
class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'gestionar academias',
            'gestionar usuarios',
            'gestionar sedes',
            'gestionar alumnos',
            'gestionar clases',
            'tomar asistencia',
            'registrar pagos',
            'gestionar examenes',
            'inscribir examenes',
            'gestionar planillas',
            'gestionar formacion',
            'ver formacion',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // admin-plataforma (dueño del sistema): todos los permisos, cruza academias.
        Role::findOrCreate('admin-plataforma')->syncPermissions($permisos);

        // federacion (Casa Central): solo lectura sobre todas las academias.
        // Se le dan los permisos para ABRIR las pantallas operativas; la escritura
        // se bloquea (User::esSoloLectura()). No ve usuarios, pagos ni academias.
        Role::findOrCreate('federacion')->syncPermissions([
            'gestionar sedes',
            'gestionar alumnos',
            'gestionar clases',
            'gestionar examenes',
            'gestionar planillas',
            'gestionar formacion',
            'ver formacion',
        ]);

        // direccion (director de un grupo): todo dentro de su academia (menos crear academias).
        Role::findOrCreate('direccion')->syncPermissions([
            'gestionar usuarios',
            'gestionar sedes',
            'gestionar alumnos',
            'gestionar clases',
            'tomar asistencia',
            'registrar pagos',
            'gestionar examenes',
            'inscribir examenes',
            'gestionar planillas',
            'gestionar formacion',
            'ver formacion',
        ]);

        // administrativo (secretaría/recepción): alumnos, clases, asistencia. SIN pagos.
        Role::findOrCreate('administrativo')->syncPermissions([
            'gestionar alumnos',
            'gestionar clases',
            'tomar asistencia',
            'ver formacion',
        ]);

        // instructor: asistencia, planillas, inscribir en exámenes, ver formación.
        // Los alumnos que ve son solo los de SUS clases (EstudiantePolicy), por eso
        // NO tiene "gestionar alumnos" (accede a la vista por la Policy viewAny).
        Role::findOrCreate('instructor')->syncPermissions([
            'tomar asistencia',
            'gestionar planillas',
            'inscribir examenes',
            'ver formacion',
        ]);

        // apoderado: sin permisos globales (ve solo a sus hijos, vía Policies).
        Role::findOrCreate('apoderado');

        // alumno: ver formación.
        Role::findOrCreate('alumno')->syncPermissions(['ver formacion']);

        // Grupo principal (una academia = un grupo). BEKHO es la federación, no un grupo.
        $academia = Academia::updateOrCreate(
            ['nombre' => 'BEKHO Power Academy'],
            ['activo' => true],
        );

        ConfiguracionPago::updateOrCreate(
            ['academia_id' => $academia->id],
            ['descuento_hermanos_pct' => 20],
        );

        // Administrador de plataforma transversal (academia_id null → ve todo).
        $admin = User::updateOrCreate(
            ['email' => 'admin@bekho.cl'],
            [
                'name' => 'Administrador BEKHO',
                'password' => Hash::make('cambiar-esto'),
                'academia_id' => null,
                'activo' => true,
            ],
        );

        $admin->syncRoles(['admin-plataforma']);
    }
}
