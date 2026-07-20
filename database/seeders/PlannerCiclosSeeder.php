<?php

namespace Database\Seeders;

use App\Enums\FilaPlannerCiclo as Fila;
use App\Enums\HabilidadVida;
use App\Models\Ciclo;
use App\Models\PlannerCiclo;
use Illuminate\Database\Seeder;

/**
 * Class planners de los 6 ciclos (grillas del Manual Legacy, pp. 1-18). Cada
 * ciclo (una Habilidad para la Vida) tiene, por fila (Warm-Up/Kicks/Forms/
 * Quadrants/Protech/Partner Drills) y bloque de semanas (1&2…7&8), su contenido.
 *
 * Transcrito de las imágenes del PDF (texto no exportable). Idempotente.
 */
class PlannerCiclosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->grillas() as $habilidad => $filas) {
            $ciclo = Ciclo::where('habilidad_vida', $habilidad)->first();
            if (! $ciclo) {
                continue;
            }

            foreach ($filas as $fila => $bloques) {
                foreach ($bloques as $bloque => $contenido) {
                    PlannerCiclo::updateOrCreate(
                        ['ciclo_id' => $ciclo->id, 'fila' => $fila, 'bloque' => $bloque],
                        ['contenido' => $contenido],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function grillas(): array
    {
        $wu = Fila::Calentamiento->value;
        $k = Fila::Patadas->value;
        $f = Fila::Formas->value;
        $q = Fila::Cuadrantes->value;
        $p = Fila::Protech->value;
        $d = Fila::DrillsParejas->value;

        return [
            HabilidadVida::Disciplina->value => [
                $wu => ['1&2' => 'Jump Rope 1 min · Push-Ups 1 min · Squats (Both Legs) 8 reps', '3&4' => 'Jump Rope 1 min · Push-Ups 1 min · Squats (Single Leg) 8 reps', '5&6' => 'Jump Rope 1 min · Push-Ups 1 min · Squat Jumps 8 reps', '7&8' => 'Jump Rope 1 min · Push-Ups 1 min · Single Leg Squat Jumps 8 reps'],
                $k => ['1&2' => 'White Belt', '3&4' => 'Orange Belt', '5&6' => 'Yellow Belt', '7&8' => 'Review'],
                $f => ['1&2' => 'White', '3&4' => 'Orange', '5&6' => 'Yellow', '7&8' => 'Review'],
                $q => ['1&2' => 'Structure — Students', '3&4' => 'Structure — Instructors', '5&6' => 'Structure — Students/Instructors', '7&8' => 'Review'],
                $p => ['1&2' => 'SSJB — Lines 1-6', '3&4' => 'DSJB — Drill #5 & #7', '5&6' => 'CBME — Golden Rooster', '7&8' => 'Review All'],
                $d => ['1&2' => '(Left Lead) Jab, Jab, Cross 15x3', '3&4' => '#2 Right Round Kick 15x3', '5&6' => '(Right Lead) Jab, Jab, Cross 15x3', '7&8' => '#2 Left Round Kick 15x3'],
            ],
            HabilidadVida::Conviccion->value => [
                $wu => ['1&2' => 'Jump Rope 1:30 min · Plank 30 sec · Front Lunges 12 each', '3&4' => 'Jump Rope 1:30 min · Plank 30 sec · Side Lunges 12 each', '5&6' => 'Jump Rope 1:30 min · Plank 1 min · Turn Lunges 12 each', '7&8' => 'Jump Rope 1:30 min · Plank 1 min · 3-Way Lunge 12 each'],
                $k => ['1&2' => 'Camo Belt', '3&4' => 'Green Belt', '5&6' => 'Purple Belt', '7&8' => 'Review'],
                $f => ['1&2' => 'Camo', '3&4' => 'Green', '5&6' => 'Purple', '7&8' => 'Review'],
                $q => ['1&2' => 'Emotion — Students', '3&4' => 'Emotion — Instructors', '5&6' => 'Emotion — Students/Instructors', '7&8' => 'Review'],
                $p => ['1&2' => 'SBME — Lines 1-6', '3&4' => 'Off. #3 & #5 Count', '5&6' => 'CBME — Crocodile Strike', '7&8' => 'Review All'],
                $d => ['1&2' => '(Left Lead) ATA Fit Test · Round #4 Combo 4x3', '3&4' => '(Left Lead) ATA Fit Test · Round #4 Combo 4x3', '5&6' => '(Left Lead) ATA Fit Test · Round #4 Combo 4x3', '7&8' => '(Left Lead) ATA Fit Test · Round #4 Combo 4x3'],
            ],
            HabilidadVida::Comunicacion->value => [
                $wu => ['1&2' => 'Jump Rope 2 min · Spiderman Push-Ups 12 each · Opposite Arm/Leg 15 each', '3&4' => 'Jump Rope 2 min · Spiderman Push-Ups 12 each · Alligators 15 each', '5&6' => 'Jump Rope 2 min · Spiderman Push-Ups 12 each · Opposite Arm/Leg 15 each', '7&8' => 'Jump Rope 2 min · Spiderman Push-Ups 12 each · Alligators 15 each'],
                $k => ['1&2' => 'Blue Belt', '3&4' => 'Brown Belt', '5&6' => 'Red Belt', '7&8' => 'Review'],
                $f => ['1&2' => 'Blue', '3&4' => 'Brown', '5&6' => 'Red', '7&8' => 'Review'],
                $q => ['1&2' => 'Knowledge — Students', '3&4' => 'Knowledge — Instructors', '5&6' => 'Knowledge — Students/Instructors', '7&8' => 'Review'],
                $p => ['1&2' => 'Mid-Range JB — Lines 1-6', '3&4' => 'Mid-Range JB — Disarm #1 & #2', '5&6' => 'CBME — Panther Strike', '7&8' => 'Review All'],
                $d => ['1&2' => '(Right Lead) ATA Fit Test · Round #4 Combo 4x3', '3&4' => '(Right Lead) ATA Fit Test · Round #4 Combo 4x3', '5&6' => '(Right Lead) ATA Fit Test · Round #4 Combo 4x3', '7&8' => '(Right Lead) ATA Fit Test · Round #4 Combo 4x3'],
            ],
            HabilidadVida::Respeto->value => [
                $wu => ['1&2' => 'Jump Rope 2:30 min · Push-Ups 1 min · Squats (Both Legs) 8 reps', '3&4' => 'Jump Rope 2:30 min · Push-Ups 1 min · Squats (Single Leg) 8 reps', '5&6' => 'Jump Rope 2:30 min · Push-Ups 1 min · Squat Jumps 8 reps', '7&8' => 'Jump Rope 2:30 min · Push-Ups 1 min · Single Leg Squat Jumps 8 reps'],
                $k => ['1&2' => 'White/Orange Belt', '3&4' => 'Yellow/Camo Belt', '5&6' => 'Green/Purple Belt', '7&8' => 'White - Purple'],
                $f => ['1&2' => 'White/Orange Belt', '3&4' => 'Yellow/Camo Belt', '5&6' => 'Green/Purple Belt', '7&8' => 'White - Purple'],
                $q => ['1&2' => 'Structure — Students/Instructors', '3&4' => 'Emotion — Students/Instructors', '5&6' => 'Structure/Emotion Quadrants', '7&8' => 'Review'],
                $p => ['1&2' => 'SSJB — Lines 1-9', '3&4' => 'DSJB — Drill #5, #7 & #9', '5&6' => 'CBME — Offensive Golden Rooster Strike', '7&8' => 'Review All'],
                $d => ['1&2' => '(Left Lead) Jab, Jab, Cross 15x3 · #2 Right Round Kick 15x3', '3&4' => '(Right Lead) Jab, Jab, Cross 15x3 · #2 Left Round Kick 15x3', '5&6' => '(Left & Right Lead) Jab, Jab, Cross 15x3 · #2 Round Kick 15x3', '7&8' => '(Left & Right Lead) Jab, Jab, Cross 15x3 · #2 Round Kick 15x3'],
            ],
            HabilidadVida::Autoestima->value => [
                $wu => ['1&2' => 'Jump Rope 3 min · Plank 1 min · Front Lunges 12 each', '3&4' => 'Jump Rope 3 min · Plank 1 min · Side Lunges 12 each', '5&6' => 'Jump Rope 3 min · Plank 1:30 min · Turn Lunges 12 each', '7&8' => 'Jump Rope 3 min · Plank 2 min · 3-Way Lunge 12 each'],
                $k => ['1&2' => 'Blue/Brown Belt', '3&4' => 'Red Belt', '5&6' => '1st Degree', '7&8' => 'Blue - 1st Degree'],
                $f => ['1&2' => 'Blue/Brown Belt', '3&4' => 'Red Belt', '5&6' => '1st Degree', '7&8' => 'Blue - 1st Degree'],
                $q => ['1&2' => 'Knowledge — Students/Instructors', '3&4' => 'Structure/Emotion — Instructors', '5&6' => 'Structure/Emotion Knowledge', '7&8' => 'Review'],
                $p => ['1&2' => 'SBME — Lines 1-9', '3&4' => 'Off. #3 & #5 / Def. 3 Count', '5&6' => 'CBME — Crocodile Strike', '7&8' => 'Review All'],
                $d => ['1&2' => '(Left Lead) ATA Fit Test · Round #4 Combo 8x2', '3&4' => '(Right Lead) ATA Fit Test · Round #4 Combo 8x2', '5&6' => '(Left & Right Lead) ATA Fit Test · Round #4 Combo 8x2', '7&8' => '(Left & Right Lead) ATA Fit Test · Round #4 Combo 8x2'],
            ],
            HabilidadVida::Honestidad->value => [
                $wu => ['1&2' => 'Jump Rope 4 min · Release Push-Ups 8x2 · Opposite Arm/Leg 15 each', '3&4' => 'Jump Rope 4 min · Release Push-Ups 8x2 · Alligators 15 each', '5&6' => 'Jump Rope 4 min · Release Push-Ups 8x2 · Opposite Arm/Leg 15 each', '7&8' => 'Jump Rope 4 min · Release Push-Ups 8x2 · Alligators 15 each'],
                $k => ['1&2' => 'White - Purple Belt', '3&4' => 'Blue - Black Belt Ranks', '5&6' => 'White - Red Belt', '7&8' => 'White - Black Belts'],
                $f => ['1&2' => 'White - Purple', '3&4' => 'Blue - Black Belt Ranks', '5&6' => 'White - Red', '7&8' => 'White - Black Belts'],
                $q => ['1&2' => 'Legacy', '3&4' => 'Structure/Emotion Knowledge', '5&6' => 'Structure/Emotion Knowledge/Legacy', '7&8' => 'Review'],
                $p => ['1&2' => 'Mid-Range JB — Lines 1-9', '3&4' => 'Mid-Range JB — Eagle Twirl & Figure 8', '5&6' => 'CBME — Panther Strike', '7&8' => 'Review All'],
                $d => ['1&2' => '(Left Lead) ATA Fit Test · Round #5 Combo 8x2', '3&4' => '(Right Lead) ATA Fit Test · Round #5 Combo 8x2', '5&6' => '(Left & Right Lead) ATA Fit Test · Round #5 Combo 8x2', '7&8' => '(Left & Right Lead) ATA Fit Test · Round #5 Combo 8x2'],
            ],
        ];
    }
}
