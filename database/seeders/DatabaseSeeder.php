<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CargosRangosSeeder::class,
            ProgramasSeeder::class,
            GradosSeeder::class,
            RolesPermisosSeeder::class,
            FormacionDemoSeeder::class,
            // Datos de demostración para ver el panel "vivo"; quitar en producción.
            DemoBekhoSeeder::class,
        ]);
    }
}
