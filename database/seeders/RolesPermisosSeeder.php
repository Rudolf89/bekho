<?php

namespace Database\Seeders;

use App\Models\Academia;
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
            'gestionar alumnos',
            'tomar asistencia',
            'registrar pagos',
            'gestionar examenes',
            'gestionar planillas',
            'gestionar formacion',
            'ver formacion',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        // Refresca la caché para que los roles vean los permisos recién creados.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // super-admin y maestro: todos los permisos.
        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->syncPermissions($permisos);

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
