<?php

namespace Database\Seeders;

use App\Support\Tenancy\Grupo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Corre como SISTEMA: el sembrado necesita ver
     * y crear datos de todos los grupos, así que el aislamiento por grupo queda
     * desactivado a propósito (los seeders que fijan un tenant para autocompletar
     * siguen haciéndolo dentro de este contexto).
     */
    public function run(): void
    {
        Grupo::comoSistema(fn () => $this->call([
            // Federación raíz (BEKHO). Fase 0 del rediseño del modelo de datos.
            FederacionesSeeder::class,
            TramosEntrenamientoSeeder::class,
            CatalogosFederacionSeeder::class,
            CompetenciaSeeder::class,
            CargosRangosSeeder::class,
            // Distintivos de collar del profesor (avance por créditos de graduación).
            DistintivosRangoSeeder::class,
            // Catálogos del Manual ATA Legacy (verificados): leyenda de formas,
            // habilidades para la vida, atributos técnicos y armas Protech.
            ManualLegacySeeder::class,
            // Juramentos confirmados por la federación (inicio/cierre + Tigers).
            JuramentosSeeder::class,
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
            // Etapas del programa Legacy (desde legacy_niveles.json) y ancla de los
            // programas a la federación. Corre tras crearse todos los programas.
            EtapasProgramaSeeder::class,
            // Instrumentos de evaluación práctica (planillero; formas y patadas).
            InstrumentosEvaluacionSeeder::class,
            // Planillas imprimibles del programa (catálogo, sin verificar aún).
            PlanillasSeeder::class,
            // Datos de demostración para ver el panel "vivo"; quitar en producción.
            // Crea directamente la capa de identidad (personas/matrículas/personal).
            DemoBekhoSeeder::class,
            // Operación de demostración por matrícula.
            DemoAsistenciaSeeder::class,
            DemoPagosSeeder::class,
            DemoExamenesSeeder::class,
            // Planilla de competencia de demostración (competidores ficticios).
            DemoCompetenciaSeeder::class,
            // Avance de demostración en el Programa Legacy (formación de instructores).
            DemoLegacySeeder::class,
        ]));
    }
}
