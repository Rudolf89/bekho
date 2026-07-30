<?php

namespace App\Livewire\Planillas;

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Class Planners de BEKHO (Beginners / Intermediate / Advanced): reproducción
 * fiel de los planificadores de clase oficiales. Cada fila del planificador
 * (Warm Up, Fórmula, Defensa, Armas, Patadas, Roturas, Combat Weapon, Sparring,
 * Anuncios) con su contenido general y el detalle por Cuadrante de Enseñanza
 * (Estructura / Emoción / Conocimiento / Legado). Referencia (solo lectura).
 */
#[Title('Class Planner')]
class ClassPlanners extends Component
{
    #[Url]
    public string $nivel = 'principiantes';

    /**
     * Los cuatro cuadrantes de enseñanza (columnas), con el examen al que se
     * asocian (Examen 1 = Estructura … Examen 4 = Legado).
     *
     * @var list<string>
     */
    private array $cuadrantes = ['Estructura', 'Emoción', 'Conocimiento', 'Legado'];

    public function render()
    {
        $planificador = $this->planificadores()[$this->nivel] ?? $this->planificadores()['principiantes'];

        return view('livewire.planillas.class-planners', [
            'niveles' => [
                'principiantes' => ['label' => 'Beginners', 'color' => 'sky'],
                'intermedio' => ['label' => 'Intermediate', 'color' => 'lime'],
                'avanzado' => ['label' => 'Advanced', 'color' => 'orange'],
            ],
            'nivelActual' => $this->nivel,
            'cuadrantes' => $this->cuadrantes,
            'planificador' => $planificador,
            'diferencias' => $this->diferencias(),
        ]);
    }

    /**
     * Filas comunes a los tres niveles + las que cambian por nivel.
     *
     * @return array<string, array{titulo: string, color: string, filas: list<array{area: string, general: ?string, cuadrantes: ?list<string>}>}>
     */
    private function planificadores(): array
    {
        $warmup = 'Cambios de guardia, movilidad articular, desplazamientos en Sparring, sentadillas y puños, '
            .'desplazamientos subiendo y bajando rodilla, puño-puño reverso y cambio de guardia, levantamiento de '
            .'pierna recto, levantamiento de pierna lateral, rodillas circulares adentro y afuera, comb. puños y patadas + burpee.';

        // Filas compartidas (cuadrantes iguales en los 3 niveles).
        $filaWarmUp = ['area' => 'Warm Up General', 'general' => $warmup, 'cuadrantes' => null];
        $filaArmas = [
            'area' => 'Armas — SJB / BME Simple (Fórmula completa)',
            'general' => 'Fórmula completa / Ángulos - Mov. específicos. Segmentos de fórmula × tiempo / Foco u objetivo.',
            'cuadrantes' => ['Coordinación', 'Velocidad', 'Intensidad', 'Poder'],
        ];
        $filaCombat = [
            'area' => 'Combat Weapon',
            'general' => 'Movilidad, 1-2-3 mezclar golpes afuera y adentro 3 zonas / Sparring libre.',
            'cuadrantes' => ['Velocidad', 'Timing', 'Amagues', 'Ataques y Defensas'],
        ];
        $filaSparring = ['area' => 'Sparring', 'general' => 'Sparring al punto / Continuo o de examen / Exhibición.', 'cuadrantes' => null];
        $filaAnuncios = ['area' => 'Anuncios y Premios', 'general' => 'Premiaciones / Nominación exámenes / Torneos / Eventos en general.', 'cuadrantes' => null];

        // Fórmula y Defensa: mismos cuadrantes, distinto contenido.
        $cuadFormula = ['Memorización/Ejecución', 'Balance/Foco', 'Torsión/Clavado', 'Cadera/Posiciones'];
        $cuadDefensa = ['Memorización/Ejecución', 'Control distancia', 'Velocidad', 'Foco'];
        $cuadPatadas = ['4 Etapas', 'Pivot', 'Clavado/Látigo', 'Postura'];
        $cuadRoturas = ['Foco', 'Precisión', 'Velocidad', 'Distancia'];

        $construir = fn (array $formula, string $defensa, array $patadas, string $roturas): array => [
            $filaWarmUp,
            ['area' => 'Fórmula — '.$formula['nombre'], 'general' => null, 'cuadrantes' => $cuadFormula],
            ['area' => 'Defensa y Ataque', 'general' => $defensa, 'cuadrantes' => $cuadDefensa],
            $filaArmas,
            ['area' => 'Patadas y Combinaciones', 'general' => $patadas['general'], 'cuadrantes' => $cuadPatadas],
            ['area' => 'Roturas', 'general' => $roturas, 'cuadrantes' => $cuadRoturas],
            $filaCombat,
            $filaSparring,
            $filaAnuncios,
        ];

        return [
            'principiantes' => [
                'titulo' => 'Beginners', 'color' => 'sky',
                'filas' => $construir(
                    ['nombre' => 'Songahm 3 (paso 14)'],
                    'Grado 9 · N.º 1 / En pareja / En pareja a la paleta o escudo.',
                    ['general' => 'Patada de Frente 1-2-3-4 · Patada de Costado 1-2-3-4 · Levantamiento recto N.º 2. '
                        .'Combinaciones: P. Frente 1 – P. Costado 3 · P. Frente 2 – P. Costado 3 · P. Frente 3 – P. Costado 3 · '
                        .'P. Costado 1 – P. Costado 3 · P. Costado 2 – P. Costado 3.'],
                    'Golpe de mazo horizontal o golpe de mazo vertical descendente.',
                ),
            ],
            'intermedio' => [
                'titulo' => 'Intermediate', 'color' => 'lime',
                'filas' => $construir(
                    ['nombre' => 'In-Wha 1 (paso 24)'],
                    'Grado 8 · N.º 1 / En pareja / En pareja a la paleta o escudo.',
                    ['general' => 'Giro de costado A-B-C-D · Giro circular A-B-C-D. '
                        .'Combinaciones: C. Adentro 2 – Giro de costado A · Giro de costado B – Vuelta patada 1 repetida · '
                        .'Giro circular tipo A – Vuelta patada 2.'],
                    'Golpe de canto vertical o golpe de canto horizontal.',
                ),
            ],
            'avanzado' => [
                'titulo' => 'Advanced', 'color' => 'orange',
                'filas' => $construir(
                    ['nombre' => 'Choong Jung 1 (paso 22)'],
                    'Grado 7 · N.º 1 / En pareja / En pareja a la paleta o escudo.',
                    ['general' => 'Patadas circulares afuera y adentro saltando 1-2-3-4 · Giro circular saltando A-B-C-D. '
                        .'Combinaciones: Circular afuera S.3 – Giro circular S.B · Afuera S.3 – P. de Frente y vuelta P. sin bajar · '
                        .'Giro circular S.C – Circular S.3 · Circular adentro S.2 – P. de Costado 1.'],
                    '1 rotura de mano - 1 de pie libre (decisión en conjunto instructor-alumno).',
                ),
            ],
        ];
    }

    /**
     * Diferencias de programas (Taekwondo vs TKD Leadership), iguales en los 3.
     *
     * @return array<string, list<string>>
     */
    private function diferencias(): array
    {
        return [
            'Alumno Taekwondo' => [
                'Fórmula tradicional hasta la mitad',
                'Fórmula de armas simple',
                'ATA MAX (Xtreme) Forms y Weapons',
            ],
            'Alumno TKD Leadership' => [
                'Fórmula tradicional completa',
                'Fórmula de armas simple y dobles',
                'ATA MAX (Xtreme) Forms y Weapons',
            ],
        ];
    }
}
