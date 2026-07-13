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

class RolesPermisosSeeder extends Seeder
{
    /**
     * Siembra permisos, roles, la academia BEKHO y el super administrador.
     */
    public function run(): void
    {
        // Limpia la caché de permisos de spatie antes de sembrar.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'gestionar usuarios',
            'gestionar sedes',
            'gestionar alumnos',
            'gestionar clases',
            'tomar asistencia',
            'registrar pagos',
            'gestionar examenes',
            'gestionar planillas',
            'gestionar formacion',
            'ver formacion',
        ];

        // "gestionar academias" es exclusivo del super-admin (crear/editar las
        // academias, que son el nivel raíz del tenant): no lo tiene el maestro.
        $permisoAcademias = 'gestionar academias';

        foreach ([...$permisos, $permisoAcademias] as $permiso) {
            Permission::findOrCreate($permiso);
        }

        // Refresca la caché para que los roles vean los permisos recién creados.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // super-admin: todos los permisos, incluida la gestión de academias.
        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->syncPermissions([...$permisos, $permisoAcademias]);

        // maestro: todos menos gestionar academias.
        $maestro = Role::findOrCreate('maestro');
        $maestro->syncPermissions($permisos);

        // instructor: tomar asistencia, gestionar planillas, ver formacion.
        $instructor = Role::findOrCreate('instructor');
        $instructor->syncPermissions([
            'tomar asistencia',
            'gestionar planillas',
            'ver formacion',
        ]);

        // alumno: ver formacion.
        $alumno = Role::findOrCreate('alumno');
        $alumno->syncPermissions(['ver formacion']);

        // apoderado: sin permisos.
        Role::findOrCreate('apoderado');

        // Academia principal.
        $academia = Academia::updateOrCreate(
            ['nombre' => 'BEKHO'],
            ['activo' => true],
        );

        // Configuración de pagos de la academia. Los montos quedan sin definir
        // (cada escuela pone los suyos); solo se fija el descuento por hermanos.
        ConfiguracionPago::updateOrCreate(
            ['academia_id' => $academia->id],
            ['descuento_hermanos_pct' => 20],
        );

        // Super administrador transversal (academia_id null → ve todas).
        $admin = User::updateOrCreate(
            ['email' => 'admin@bekho.cl'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('cambiar-esto'),
                'academia_id' => null,
                'activo' => true,
            ],
        );

        $admin->syncRoles(['super-admin']);
    }
}
