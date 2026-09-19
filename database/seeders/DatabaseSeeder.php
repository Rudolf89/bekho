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
            // Federación raíz (BEKHO). Fase 0 del rediseño del modelo de datos.
            FederacionesSeeder::class,
            TramosEntrenamientoSeeder::class,
            CatalogosFederacionSeeder::class,
            CargosRangosSeeder::class,
            ProgramasSeeder::class,
            GradosSeeder::class,
            RolesPermisosSeeder::class,
            FormacionDemoSeeder::class,
            // Contenido real de "Aprender": preparación para examen de juez N1.
            PreparacionJuezSeeder::class,
            // Contenido de "Aprender": manuales ATA (Legacy, Tigers, MAK, MAX N1/N2).
            ManualesAprenderSeeder::class,
            // Cuestionarios autocorregidos (catálogo compartido): banco de juez ATA.
            CuestionariosSeeder::class,
            // Contenido pedagógico real del Planificador Unificado (catálogos
            // compartidos + planillas grupo × nivel de la primera grupo).
            PlanificadorSeeder::class,
            // Class planners de los 6 ciclos (grillas del Manual Legacy).
            PlannerCiclosSeeder::class,
            // Biblioteca de técnicas del currículo ATA (catálogo compartido).
            TecnicasSeeder::class,
            // Paso a paso de las formas Songahm (Manual Legacy).
            FormasPasosSeeder::class,
            // Enlace técnica ↔ cinturón (requiere grados y técnicas ya sembrados).
            GradoTecnicaSeeder::class,
            // Patadas detalladas por grado (reemplazan el resumen de esos cinturones).
            PatadasGradoSeeder::class,
            // Cuadrantes de Enseñanza (marco pedagógico ATA, catálogo compartido).
            CuadrantesSeeder::class,
            // Catálogo de recompensas/gamificación (catálogo compartido).
            RecompensasSeeder::class,
            // Programa Legacy: niveles y requisitos (catálogo compartido).
            LegacySeeder::class,
            // Datos de demostración para ver el panel "vivo"; quitar en producción.
            DemoBekhoSeeder::class,
            // Rediseño Fase 2: deriva la capa de identidad (personas/matrículas)
            // desde la operación actual. Aditivo; el recableo llega en la Fase 4.
            MigraPersonasSeeder::class,
        ]);
    }
}
