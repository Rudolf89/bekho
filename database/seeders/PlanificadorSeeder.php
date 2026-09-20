<?php

namespace Database\Seeders;

use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\NivelEntrenamiento;
use App\Enums\TipoBloque;
use App\Models\CategoriaCalentamiento;
use App\Models\Ciclo;
use App\Models\CurriculoNivel;
use App\Models\LeccionVida;
use App\Models\PlanificacionCinturonNegro;
use App\Models\PlanificacionClase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contenido pedagógico del "Planificador Unificado" (fuente:
 * docs/planificador-unificado.tsx). Todo es idempotente.
 *
 * IMPORTANTE: este contenido fue GENERADO CON IA en el prototipo, NO transcrito
 * de los manuales ATA. Se siembra marcado con fuente/verificado=false para que la
 * UI lo advierta como material de referencia sin validar por la federación (no se
 * corrige ni completa aquí: requiere los planner reales del manual en español).
 *
 * Catálogos compartidos (sin grupo_id): biblioteca de calentamiento, currículo
 * por nivel, lecciones de vida y planificador de Cinturón Negro. Las
 * planificaciones grupo × nivel (con sus bloques) también son transversales.
 *
 * Correcciones al portar desde el prototipo: kids → For Kids, adults → Jóvenes y
 * Adultos, Tigres → Tigers; "Creencia" → "Convicción". "Combat Weapon" y
 * "Sparring" se mantienen en inglés.
 */
class PlanificadorSeeder extends Seeder
{
    /** Procedencia del contenido sembrado (generado con IA, sin validar). */
    private const FUENTE = 'planificador-unificado.tsx (generado con IA)';

    public function run(): void
    {
        $this->sembrarCiclos();
        $this->sembrarCalentamiento();
        $this->sembrarCurriculos();
        $this->sembrarLecciones();
        $this->sembrarCinturonNegro();
        $this->sembrarPlanificaciones();
    }

    // ── Ciclos (backbone: 6 Habilidades de Vida) ────────────────────────────

    private function sembrarCiclos(): void
    {
        $ciclos = [
            HabilidadVida::Disciplina, HabilidadVida::Conviccion, HabilidadVida::Comunicacion,
            HabilidadVida::Respeto, HabilidadVida::Autoestima, HabilidadVida::Honestidad,
        ];

        foreach ($ciclos as $i => $habilidad) {
            // Enlaza a la habilidad del catálogo del manual por nombre (si existe).
            $habilidadId = \App\Models\HabilidadVida::where('nombre', $habilidad->etiqueta())->value('id');

            Ciclo::updateOrCreate(
                ['habilidad_vida' => $habilidad->value],
                [
                    'habilidad_vida_id' => $habilidadId,
                    'nombre' => 'Ciclo '.($i + 1).' · '.$habilidad->etiqueta(),
                    'orden' => $i + 1,
                    'semanas' => 8,
                ],
            );
        }
    }

    // ── Biblioteca de calentamiento ─────────────────────────────────────────

    private function sembrarCalentamiento(): void
    {
        $tigers = GrupoEtario::Tigers->value;
        $kids = GrupoEtario::ForKids->value;
        $adults = GrupoEtario::JovenesAdultos->value;
        $todos = [$tigers, $kids, $adults];

        // [clave, nombre, color, grupos, ejercicios[[nombre, desc]]]
        $categorias = [
            ['guardia', 'Cambios de Guardia y Desplazamientos', '#0077b6', $todos, [
                ['Cambio de guardia básico', 'Guardia izquierda → derecha → izquierda. x8.'],
                ['Adelante – atrás – cambio de guardia', 'Paso adelante, paso atrás, cambio de guardia. x8 cada lado.'],
                ['Lateral – lateral – cambio de guardia', 'Paso lateral derecha, izquierda, cambio de guardia. x8.'],
                ['Desplazamiento en L', 'Diagonal derecha, diagonal izquierda, cambio de guardia. x6.'],
                ['Avanzar y retroceder x4 + cambio', '4 pasos adelante + 4 atrás + cambio de guardia. x4 series.'],
                ['Círculo con cambio de guardia', 'Desplazarse en círculo cambiando guardia cada 2 pasos. Ambas direcciones.'],
            ]],
            ['punos', 'Puños y Combinaciones de Manos', '#c1440e', $todos, [
                ['Puño – Puño reverso', 'Puño directo + puño reverso. x10 por lado.'],
                ['Puño – Puño reverso – Puño', 'Combinación 3 tiempos. Retracción rápida. x8 por lado.'],
                ['Doble puño avanzando', 'Paso adelante + doble puño (izq–der). Retroceder + doble puño. x8.'],
                ['Puño bajo – puño alto', 'Puño solar plexus + puño cabeza. Alternar lados. x10 por lado.'],
                ['Puño reverso – cambio – puño reverso', 'Puño reverso derecho, cambio de guardia, puño reverso izquierdo. x8.'],
                ['3 puños rápidos + cambio de guardia', '3 puños continuos rápidos + cambio de guardia. x6 series.'],
                ['Puños continuos x10 + cambio', '10 puños alternados rápidos + cambio de guardia. x4 series.'],
            ]],
            ['combopunos', 'Combinaciones de Puños', '#b5179e', [$kids, $adults], [
                ['Combinación 1 – Puño de adelante', 'Puño directo con la mano delantera. Retracción completa. x10 por lado.'],
                ['Combinación 2 – Puño / Puño reverso', 'Puño directo (mano delantera) + puño reverso (mano trasera). x8 por lado.'],
                ['Combinación 3 – Puño reverso / Gancho / Puño reverso', 'Puño reverso + gancho + puño reverso. Guardia alta entre golpes. x6 por lado.'],
                ['Combinación 4 – Puño / Puño reverso / Gancho / Puño reverso', '4 tiempos completos. Primero lento con forma, luego a velocidad. x6 por lado.'],
                ['Combinación 4 avanzando', 'Comb. 4 con paso adelante al iniciar y paso atrás al finalizar. x6 series.'],
                ['Combinación 4 ×2 + cambio de guardia', 'Comb. 4 dos veces seguidas + cambio de guardia + repetir del otro lado. x4 series.'],
            ]],
            ['piernas', 'Sentadillas y Levantamiento de Piernas', '#2d6a4f', $todos, [
                ['Sentadilla + patada de frente', 'Sentadilla → levantarse → patada de frente. Alternar piernas. x8 por lado.'],
                ['Sentadilla + rodilla al pecho', 'Sentadilla → subir rodilla al pecho alternada. x10.'],
                ['Sentadilla + levantamiento lateral', 'Sentadilla → levantamiento de pierna lateral. x8 por lado.'],
                ['Sentadilla sumo + doble rodilla', 'Pies abiertos → al subir, rodilla derecha + izquierda alternadas. x8.'],
                ['Levantamiento recto de pierna', 'Pierna recta al frente, espalda erguida, sin doblar rodilla. x10 por lado. Progresión: lento con pausa → dinámico.'],
                ['Levantamiento circular hacia adentro', 'Pierna recta al frente trazando semicírculo hacia adentro hasta el lateral. x8 por lado.'],
                ['Levantamiento circular hacia afuera', 'Pierna recta al lateral trazando semicírculo hacia afuera hasta el frente. x8 por lado.'],
                ['Circular adentro + afuera continuo', 'Sin bajar la pierna: circular adentro → circular afuera en continuo. x6 por lado.'],
                ['Rodillas circulares adentro', 'Levantar rodilla y hacer círculo completo hacia adentro antes de bajar. x8 por lado.'],
                ['Rodillas circulares afuera', 'Levantar rodilla y hacer círculo completo hacia afuera antes de bajar. x8 por lado.'],
            ]],
            ['combo', 'Combinaciones Mixtas', '#7b2d8b', [$kids, $adults], [
                ['Puño – Puño reverso – Patada de frente', 'Puño directo + reverso + patada de frente con pierna trasera. x6 por lado.'],
                ['Cambio de guardia – Puño – Patada lateral', 'Cambio de guardia + puño directo + patada lateral. x6 por lado.'],
                ['2 Puños – Sentadilla – Cambio de guardia', 'Doble puño + sentadilla + cambio de guardia + doble puño. x8 series.'],
                ['Avanzar – Puño – Retroceder – Puño reverso', 'Paso adelante + puño directo + paso atrás + puño reverso. x8.'],
                ['Sentadilla – Puño – Puño reverso – Patada', 'Sentadilla → levantarse → puño + reverso → patada de frente. x6 por lado.'],
                ['3 pasos + ráfaga de puños + cambio', 'Avanzar 3 pasos + 5 puños rápidos + cambio de guardia + retroceder. x4 series.'],
                ['Rodilla – Patada de frente – Puño', 'Rodilla al pecho + extender en patada de frente + puño al bajar. x6 por lado.'],
            ]],
            ['cardio', 'Cardio y Resistencia', '#f77f00', [$adults], [
                ['Burpee + cambio de guardia', 'Burpee completo → cambio de guardia + 2 puños. x8.'],
                ['Burpee + patada de frente', 'Burpee completo → patada de frente alternada. x8.'],
                ['Saltos en guardia', 'Saltos cortos alternando pie delantero/trasero rápidamente. 20 seg + pausa. x4.'],
                ['Carrera en sitio + cambio de guardia', 'Trotar en sitio x10 seg + cambio de guardia súbito. x6.'],
                ['Combinación puños + patadas + burpee', '5 puños alternados + patada frente derecha + izquierda + 1 burpee. x6.'],
                ['Saltar y cambiar de guardia', 'Salto cambiando pies en el aire. Aterrizar en guardia opuesta. x10.'],
            ]],
        ];

        foreach ($categorias as $orden => [$clave, $nombre, $color, $grupos, $ejercicios]) {
            $categoria = CategoriaCalentamiento::updateOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'color' => $color, 'orden' => $orden + 1, 'fuente' => self::FUENTE, 'verificado' => false],
            );
            $categoria->sincronizarGrupos($grupos);

            foreach ($ejercicios as $i => [$nombreEj, $desc]) {
                $categoria->ejercicios()->updateOrCreate(
                    ['nombre' => $nombreEj],
                    ['descripcion' => $desc, 'orden' => $i + 1],
                );
            }
        }

        // Notas por grupo (warmupNotes).
        $notas = [
            GrupoEtario::Tigers->value => 'Usar solo Comb. 1 y 2 de puños. Omitir burpees y ganchos. Conteo cantado y música.',
            GrupoEtario::ForKids->value => 'Comb. 1 a 4 según cinturón. Primero lento con forma, luego a velocidad.',
            GrupoEtario::JovenesAdultos->value => 'Todas las categorías. Énfasis en retracción, velocidad y cambio de guardia entre combos.',
        ];
        foreach ($notas as $grupo => $nota) {
            DB::table('notas_calentamiento')->updateOrInsert(
                ['grupo_etario' => $grupo],
                ['nota' => $nota, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    // ── Currículo por nivel ─────────────────────────────────────────────────

    private function sembrarCurriculos(): void
    {
        $curriculos = [
            NivelEntrenamiento::Principiantes->value => [
                'formula' => 'Songahm 3 (Paso 14)',
                'defensa' => 'Grado 9 – N°3',
                'patadas' => 'Circular Afuera 1,2,3,4 / Patada de Frente Saltando 1,2,3,4',
                'combinaciones' => ['Circular A.3 – Vuelta 2', 'P. de Frente S.3 – Circular A.3', 'P. de Frente S.2 – Circular A.3', 'Circular A.1 – P. de Frente S.2'],
                'roturas' => 'Combinación opcional de períodos anteriores',
            ],
            NivelEntrenamiento::Intermedio->value => [
                'formula' => 'In-Wha 1 (Paso 24)',
                'defensa' => 'Grado 8 – N°3',
                'patadas' => 'Gancho 1,2,3,4 / Giro Gancho A,B,C,D',
                'combinaciones' => ['Gancho 3 – Giro Gancho A', 'Giro Gancho D – P. de Costado 3', 'Gancho 1 – Giro Gancho C', 'Vuelta P.2 Repetida – Giro Gancho A'],
                'roturas' => 'Combinar períodos anteriores de manera opcional',
            ],
            NivelEntrenamiento::Avanzado->value => [
                'formula' => 'Choong Jung 1 (Paso 22)',
                'defensa' => 'Grado 7 – N°3',
                'patadas' => 'Gancho Saltando 1,2,3,4 / Giro Gancho Saltando A,B,C,D / Giro Mariposa',
                'combinaciones' => ['Gancho S.3 – Giro Mariposa', 'Giro Gancho S.A – Gancho S.3', 'Giro Mariposa con paso – Giro Gancho C'],
                'roturas' => '1 rotura de mano + 1 de pie libre (decisión instructor–alumno)',
            ],
        ];

        foreach ($curriculos as $nivel => $datos) {
            CurriculoNivel::updateOrCreate(['nivel' => $nivel], $datos);
        }
    }

    // ── Lecciones de Vida ───────────────────────────────────────────────────

    private function sembrarLecciones(): void
    {
        // Lecciones de vida reales (aportadas por la escuela). El resto de las
        // semanas/ciclos queda pendiente: NO se inventan.
        $lecciones = [
            [HabilidadVida::Disciplina, 7, [
                'comienzo_texto' => 'VISIÓN es uno de los pilares más importantes de disciplina. Los alumnos disciplinados siempre tendrán presente su visión para alcanzar sus objetivos. ¿Cuál es su objetivo hoy?',
                'comienzo_frase' => 'VISUALICE SUS OBJETIVOS',
                'durante_texto' => 'Se acerca el examen, así que visualicemos cómo se ve un campeón durante un examen. Un campeón es fuerte, confiado y tiene un alto nivel de disciplina.',
                'durante_frase' => 'VISUALICE SUS LOGROS',
                'fin_texto' => 'Un líder en la casa siempre tiene la visión de cómo debe comportarse, cómo debe lucir su habitación e incluso qué tan bien le va a ir en la escuela. ¿Quién usa visualización en casa?',
                'fin_frase' => 'PONGA SU VISIÓN EN ACCIÓN',
            ]],
            // Ciclo Comunicación completo (8 semanas), aportado por la escuela.
            [HabilidadVida::Comunicacion, 1, [
                'comienzo_texto' => 'Este ciclo, vamos a aprender acerca de la comunicación. La comunicación es lo que me conecta con el mundo. Así que hoy vamos a trabajar en cómo nos vemos, y quiero que se vea como un líder.',
                'comienzo_frase' => 'La comunicación es lo que me conecta con el mundo',
                'durante_texto' => 'Los líderes prestan atención a su apariencia. Esto significa que se encargan de que su uniforme se encuentre siempre limpio y su cinturón bien atado.',
                'durante_frase' => 'LUCIR como un LÍDER',
                'fin_texto' => '¡Hoy hablamos de cómo nos vemos! En casa, lo hacemos cuidando nuestra higiene personal. Ducharse y cepillarse los dientes todos los días son solo 2 ejemplos y son tareas que nuestros padres no deberían tener que decirnos que hagamos.',
                'fin_frase' => 'La higiene personal es su responsabilidad',
            ]],
            [HabilidadVida::Comunicacion, 2, [
                'comienzo_texto' => 'Esta semana, continuaremos trabajando en cómo nos vemos. Recuerde, será menos propenso a convertirse en víctima de los ladrones si luce y actúa confiado. Debemos transmitir confianza manteniendo el pecho erguido, mirando hacia adelante y haciendo contacto visual.',
                'comienzo_frase' => 'Manténgase erguido y haga contacto visual',
                'durante_texto' => '¿Recuerda al principio de la clase cuando hablamos de lucir confiado? Si realiza su defensa personal en clase, realícela con confianza actuando rápidamente y gritando fuerte.',
                'durante_frase' => 'ACTUAR con CONFIANZA',
                'fin_texto' => 'La comunicación es la herramienta para conectarse con sus padres. Ellos estarán más contentos si usamos el contacto visual y el lenguaje corporal adecuados al escucharlos.',
                'fin_frase' => 'Comunicación adecuada con los padres',
            ]],
            [HabilidadVida::Comunicacion, 3, [
                'comienzo_texto' => 'En esta semana vamos a enfocarnos en escuchar. Una de las mejores maneras de demostrar que está escuchando en clase es respondiendo de manera segura, «Sí señor / señora». Vamos a ver quién se comunica mejor hoy.',
                'comienzo_frase' => 'Responder fuerte',
                'durante_texto' => 'Todos tenemos nuestra propia voz interior. Tu voz interior debe siempre decirte que puedes hacerlo y que eres capaz. Confía en esta voz cuando estés rompiendo las tablas.',
                'durante_frase' => 'Escuche sus pensamientos POSITIVOS',
                'fin_texto' => 'Escuchar es probablemente el aspecto más importante en la comunicación con sus padres. Concéntrese en escuchar lo que dicen esta semana y siga sus indicaciones.',
                'fin_frase' => 'Escuche a sus padres',
            ]],
            [HabilidadVida::Comunicacion, 4, [
                'comienzo_texto' => 'Hoy, vamos a practicar buena comunicación escuchando y reaccionando inmediatamente al recibir un comando. Ahora vamos a practicarlo… «¡Firmes!»',
                'comienzo_frase' => 'Escuchar y REACCIONAR',
                'durante_texto' => 'Anteriormente hablamos sobre hacer las cosas inmediatamente. Si se enfoca y hace contacto visual, sus habilidades para escuchar mejorarán. Vamos a aplicar este concepto cuando hagamos sparring. Cada vez que yo diga la palabra «parar» usted parará inmediatamente.',
                'durante_frase' => 'Reaccionar INMEDIATAMENTE',
                'fin_texto' => 'La semana pasada, nos concentramos en escuchar a nuestros padres. Esta semana, vamos a agregar a nuestros hermanos. Sin enojarse ni gritar debe escuchar primero y luego comunicarse correctamente.',
                'fin_frase' => 'Comunicación adecuada con los hermanos',
            ]],
            [HabilidadVida::Comunicacion, 5, [
                'comienzo_texto' => 'El lenguaje verbal es probablemente la forma más común de comunicación y una parte importante de ello es la forma en que nos hablamos a nosotros mismos. Hoy, durante la clase, vamos a decirnos «sí puedo» frente a cualquier desafío que se nos presente. Esto creará el hábito de hablarnos a nosotros mismos de forma positiva.',
                'comienzo_frase' => 'Dígase frases POSITIVAS',
                'durante_texto' => 'Frente al desafío de romper la tabla, tendrá una batalla interna. Probablemente se dirá cosas como «esto va a doler», o «no creo que sea lo suficientemente fuerte». Debe superar estos pensamientos diciéndose a usted mismo que puede hacerlo.',
                'durante_frase' => 'Dígase a sí mismo que puede hacerlo',
                'fin_texto' => 'Durante esta semana, su objetivo será usar el tono de voz adecuado cuando esté hablando con sus padres. Una voz suave y respetuosa es la mejor manera de comunicarse con ellos.',
                'fin_frase' => 'Diríjase a sus padres en un tono de voz apropiado',
            ]],
            [HabilidadVida::Comunicacion, 6, [
                'comienzo_texto' => 'Hoy, estamos trabajando en uno de los niveles más importantes de la comunicación que es el hablar. Antes de empezar la clase, giremos hacia la audiencia y digamos un «hola» con confianza a todos los padres.',
                'comienzo_frase' => 'Saludar con CONFIANZA',
                'durante_texto' => 'Vamos a trabajar la comunicación entre nosotros, cuidando siempre las palabras que elegimos para comunicarnos. Cuando alguien va a realizar un ejercicio, vamos a alentarlo.',
                'durante_frase' => 'Usar palabras ALENTADORAS',
                'fin_texto' => '¡Felicitaciones por ganar su tira esta semana! Otra manera de seguir mejorando en cómo nos comunicamos es usar las palabras apropiadas con nuestros padres. Decir cosas como «por favor», «gracias» y «de nada». ¿Quién está aplicando esto en casa? ¡Genial, a seguir así!',
                'fin_frase' => 'Usa tus modales.',
            ]],
            [HabilidadVida::Comunicacion, 7, [
                'comienzo_texto' => '¡Ya casi terminamos con el ciclo de comunicación! Durante las próximas 2 semanas, estaremos trabajando en cómo liderar. El mejor liderazgo es con el ejemplo. Hoy, quiero ver quién va a liderar en la clase comportándose como un ejemplo a seguir.',
                'comienzo_frase' => 'Liderar con el ejemplo',
                'durante_texto' => 'Liderar es probablemente la mejor manera de mostrar buena comunicación en clase. Vamos a ver quién responde como un líder contestando cuando se dé un comando en clase.',
                'durante_frase' => 'Responder como un líder',
                'fin_texto' => 'He estado hablando de liderar durante toda la clase. Eso significa que ahora usted debe ser capaz de ir a casa y ser un líder entre sus hermanos y amigos, cumplir con lo que sus padres piden sin contestar o protestar. En otras palabras, debemos recordar liderar con el ejemplo.',
                'fin_frase' => 'Sé un modelo de liderazgo para otros',
            ]],
            [HabilidadVida::Comunicacion, 8, [
                'comienzo_texto' => '¡Felicidades a todos ustedes! Han llegado a la última semana del ciclo de comunicación. Vamos a resumir lo aprendido. Trabajamos en mantener una buena apariencia, en cómo escuchamos, hablamos y finalmente lideramos. Recuerden, los líderes toman ACCIÓN. ¿Eres un líder?',
                'comienzo_frase' => 'Los líderes toman acción',
                'durante_texto' => 'Como usted sabe, vamos a tener el examen de cambio de cinturón la próxima semana. Los líderes se lucirán durante este examen porque van a convertirse en modelos a seguir. ¿Quién se convertirá en líder esta última semana y probablemente gane la invitación a la clase de LEADERSHIP?',
                'durante_frase' => 'Los líderes obtienen RECOMPENSAS',
                'fin_texto' => 'Sé un líder esta semana y tome acción en su casa haciendo las tareas antes que tus padres le pregunten, como ordenar tu habitación, limpiar los platos o haciendo todo lo que crea que será útil.',
                'fin_frase' => 'Los líderes ayudan sin que se les pregunte',
            ]],
        ];

        foreach ($lecciones as [$habilidad, $semana, $campos]) {
            $ciclo = Ciclo::where('habilidad_vida', $habilidad->value)->first();
            if (! $ciclo) {
                continue;
            }

            LeccionVida::updateOrCreate(
                ['ciclo_id' => $ciclo->id, 'semana' => $semana],
                $campos,
            );
        }
    }

    // ── Planificador de Cinturón Negro ──────────────────────────────────────

    private function sembrarCinturonNegro(): void
    {
        $tigers = GrupoEtario::Tigers->value;
        $kids = GrupoEtario::ForKids->value;
        $adults = GrupoEtario::JovenesAdultos->value;

        $semanas = [
            ['s12', 'Sem. 1 & 2', 'Velocidad / Explosión', '⚡', '#1d3557', [
                'warmup_general' => ['Puños rectos continuos a velocidad máxima', 'Ganchos alternados izquierda / derecha', 'Upper cut doble + cambio de guardia', 'Combinación: recto – gancho – upper cut x4'],
                'warmup_especifico' => ['Sparring Combos al aire: Combo 1, 2, 3 y 4 en secuencia', 'Mismo trabajo con paletas en parejas'],
                'basicos' => ['Básicos: B.A. Fórmula (énfasis en velocidad)', 'En pareja con paletas – respuesta rápida al blanco', 'Con escudos – potencia + velocidad en cada golpe'],
                'sparring' => ['Combo 1 al aire', 'Combo 2 al aire / paletas', 'Combo 3 al aire / paletas', 'Combo 4 al aire / paletas'],
                'anuncios' => ['Nominación Exámenes', 'Torneo Nacional'],
            ], [
                $tigers => 'Solo Combo 1 y 2. Upper cut como demostración. Paletas bajas.',
                $kids => 'Combos 1 al 4 según cinturón. Paletas supervisadas. Corrección de retracción.',
                $adults => 'Los 4 combos a máxima velocidad. Paletas + escudos en parejas libres.',
            ]],
            ['s34', 'Sem. 3 & 4', 'Defensa / Contra Ataque', '🛡️', '#2d6a4f', [
                'warmup_general' => ['Cross Jacks x20', '10 sentadillas estáticas + 10 sentadillas con salto', 'Balística al frente, atrás y a los lados', 'Flexiones de brazo x10–15', 'Abdominales x20'],
                'warmup_especifico' => ['Esquives básicos: izquierda, derecha, atrás', 'Bloqueos 1 al 5 en pareja – respuesta rápida'],
                'basicos' => ['P.A. Fórmula – Patadas y Ataques', 'Patadas con esquives integrados', 'En pareja con paletas – ataque y respuesta defensiva', 'Con escudos – esquive + contraataque inmediato'],
                'sparring' => ['Sparring al punto (1 punto = cambio de pareja)', 'Ronda y sale al punto', 'Solo manos – bloqueo y contraataque', 'Solo pies – lectura de distancia y reacción'],
                'anuncios' => ['Exámenes / Torneos', 'PANAM'],
            ], [
                $tigers => 'Omitir cross jacks y balística. 5 sentadillas simples. Esquive básico: paso atrás.',
                $kids => 'Cross jacks y sentadillas x8. Esquive + 1 contraataque. Solo manos supervisado.',
                $adults => 'Circuito completo. Esquive + contraataque libre. Sparring al punto y continuo.',
            ]],
            ['s56', 'Sem. 5 & 6', 'Leer al Oponente', '👁️', '#c1440e', [
                'warmup_general' => ['Movimientos articulares completos (cuello, hombros, caderas, tobillos)', 'Rotaciones de cadera en guardia', 'Movilidad activa: levantamiento recto + circular de pierna'],
                'warmup_especifico' => ['Puños, esquives y bloqueos 1 al 5 – lectura del compañero', 'Fórmula 4 veces seguidas sin pausa', 'En parejas con paletas – el que ataca elige el blanco, el otro reacciona'],
                'basicos' => ['Fórmulas 4 veces en parejas con paletas', 'Énfasis en lectura del oponente antes de atacar'],
                'sparring' => ['Combat Weapon – Ángulo 4 y Ángulo 2', 'Guardia abierta vs cerrada – identificar y explotar', 'Sparring de reacción – instructor da la señal', 'Ataques del alumno vs contraataques', 'Sparring 5 puntos – alta concentración'],
                'anuncios' => ['Seminarios Grupo 2', 'PANAM'],
            ], [
                $tigers => 'Leer al oponente como juego: ¿de qué lado viene la paleta? Máx. 2 bloqueos.',
                $kids => 'Fórmula 2 veces seguidas. Lectura con señal visual (paleta de color).',
                $adults => 'Fórmula 4 veces sin pausa. Guardia abierta/cerrada con decisión propia. Sparring 5 puntos.',
            ]],
            ['s78', 'Sem. 7 & 8', 'Poder y Decisión', '💪', '#7b2d8b', [
                'warmup_general' => ['Calentamiento completo a criterio del instructor', 'Énfasis en activación de cadera y core'],
                'warmup_especifico' => ['Puños, esquive y bloqueos 1 al 5 – máxima potencia', 'Movilidad 4 – secuencia completa de desplazamientos'],
                'basicos' => ['Segmentos de fórmula con máxima potencia', 'Armas: Fórmula AR. completa', 'Énfasis en decisión de técnica según situación'],
                'sparring' => ['10 sparrings de 2 minutos con diferentes compañeros', '20 sparrings de 2 minutos (alta intensidad)', '4 series de movilidad táctica entre rondas', '8 series de movilidad táctica entre rondas'],
                'anuncios' => ['Seminarios Grupo 2', 'PANAM'],
            ], [
                $tigers => 'Solo segmentos cortos de fórmula. Sin sparring intenso. Juego de reacción.',
                $kids => 'Segmentos de fórmula x potencia. Máx. 4 rondas de 1 minuto.',
                $adults => 'Alta exigencia. 10–20 sparrings de 2 min. Movilidad táctica entre rondas.',
            ]],
        ];

        foreach ($semanas as $orden => [$clave, $label, $tema, $icono, $color, $secciones, $adapt]) {
            $plan = PlanificacionCinturonNegro::updateOrCreate(
                ['clave' => $clave],
                ['label' => $label, 'tema' => $tema, 'icono' => $icono, 'color' => $color, 'orden' => $orden + 1, 'fuente' => self::FUENTE, 'verificado' => false],
            );

            $plan->secciones()->delete();
            foreach ($secciones as $seccion => $items) {
                foreach ($items as $i => $item) {
                    $plan->secciones()->create(['seccion' => $seccion, 'item' => $item, 'orden' => $i + 1]);
                }
            }

            foreach ($adapt as $grupo => $texto) {
                $plan->adaptaciones()->updateOrCreate(['grupo_etario' => $grupo], ['texto' => $texto]);
            }
        }
    }

    // ── Planificaciones grupo × nivel (transversales) ─────────────────────────────

    private function sembrarPlanificaciones(): void
    {
        $niveles = [
            NivelEntrenamiento::Principiantes,
            NivelEntrenamiento::Intermedio,
            NivelEntrenamiento::Avanzado,
        ];

        foreach ($niveles as $nivel) {
            $curriculo = CurriculoNivel::where('nivel', $nivel->value)->first();
            if (! $curriculo) {
                continue;
            }

            foreach (GrupoEtario::cases() as $grupo) {
                $planificacion = PlanificacionClase::updateOrCreate(
                    ['grupo_etario' => $grupo->value, 'nivel' => $nivel->value, 'programa_id' => null],
                    [
                        'nombre' => $grupo->etiqueta().' · '.$nivel->etiqueta(),
                        'habilidad_vida' => HabilidadVida::Disciplina->value,
                        'activo' => true,
                        'fuente' => self::FUENTE,
                        'verificado' => false,
                    ],
                );

                $bloques = $this->bloquesDe($grupo, $curriculo);

                // Idempotente: se regeneran los bloques del planificador.
                $planificacion->bloques()->delete();
                foreach ($bloques as $orden => $bloque) {
                    $planificacion->bloques()->create([
                        'tipo' => $bloque['tipo']?->value,
                        'tiempo' => $bloque['tiempo'],
                        'titulo' => $bloque['titulo'],
                        'contenido' => $bloque['detalle'],
                        'cuadrante_texto' => $bloque['cuadrante'],
                        'cuadrante_color' => self::CUAD_COLOR[$bloque['cuadrante']] ?? '#6c757d',
                        'orden' => $orden + 1,
                    ]);
                }
            }
        }
    }

    /**
     * Bloques de una planilla según grupo etario, con el detalle rellenado desde
     * el currículo del nivel. Cada bloque devuelve
     * [tipo, tiempo, titulo, detalle, cuadrante].
     *
     * @return list<array{tipo: ?TipoBloque, tiempo: string, titulo: string, detalle: string, cuadrante: string}>
     */
    private function bloquesDe(GrupoEtario $grupo, CurriculoNivel $c): array
    {
        $f = $c->formula;
        $d = $c->defensa;
        $p = $c->patadas;
        $r = $c->roturas;
        $combos = collect($c->combinaciones ?? [])
            ->map(fn ($x, $i) => ($i + 1).'. '.$x)
            ->implode('  |  ');

        return match ($grupo) {
            GrupoEtario::Tigers => [
                ['tipo' => TipoBloque::Calentamiento, 'tiempo' => '0–5 min', 'titulo' => '🔥 Warm Up', 'detalle' => 'Ronda circular con música: cambios de guardia cantados, saltos, aplaudir en guardia. Máx. 2 ejercicios seguidos. Saludo grupal final.', 'cuadrante' => 'Estructura'],
                ['tipo' => TipoBloque::Formula, 'tiempo' => '5–12 min', 'titulo' => "⭐ Fórmula: {$f}", 'detalle' => 'Instructor al frente como espejo. Contar en coreano con palmadas. Solo pasos ya aprendidos. Aplausos por logros.', 'cuadrante' => 'Memorización / Emoción'],
                ['tipo' => TipoBloque::DefensaYAtaque, 'tiempo' => '12–20 min', 'titulo' => '🥋 Defensa y Ataque', 'detalle' => "{$d}. Movimientos con imagen visual. Escudos de colores. Siempre dirigido por el instructor.", 'cuadrante' => 'Memorización'],
                ['tipo' => TipoBloque::Patadas, 'tiempo' => '20–28 min', 'titulo' => '🦵 Patadas y Combinaciones', 'detalle' => 'Solo patada de frente y circular básica con apoyo en barra. Demostración breve de la combinación más simple del nivel.', 'cuadrante' => 'Postura / Equilibrio'],
                ['tipo' => TipoBloque::Roturas, 'tiempo' => '28–33 min', 'titulo' => '💥 Roturas', 'detalle' => '1 técnica sencilla con foam. Cada alumno rompe con ayuda y recibe aplausos.', 'cuadrante' => 'Foco / Confianza'],
                ['tipo' => null, 'tiempo' => '33–39 min', 'titulo' => '⚔️ Juego de Golpes', 'detalle' => 'Instructor sostiene escudo, alumno golpea según color/número. Sin armas ni sparring libre.', 'cuadrante' => 'Velocidad / Reacción'],
                ['tipo' => null, 'tiempo' => '39–42 min', 'titulo' => '🥊 Mini Sparring', 'detalle' => 'Juego de tocar hombro/rodilla. 30 seg por turno. Supervisión directa.', 'cuadrante' => 'Coordinación'],
                ['tipo' => null, 'tiempo' => '42–45 min', 'titulo' => '🏆 Cierre y Premio', 'detalle' => 'Sello o sticker de esfuerzo. Instructor menciona 1 logro de cada niño. Despedida con saludo formal en coreano.', 'cuadrante' => 'Legado'],
            ],
            GrupoEtario::ForKids => [
                ['tipo' => TipoBloque::Calentamiento, 'tiempo' => '0–7 min', 'titulo' => '🔥 Warm Up', 'detalle' => 'Cambios de guardia, movilidad articular básica, desplazamientos en sparring, 10 sentadillas + puños, combinación puños + patada al frente + burpee. Dirigido por un alumno líder.', 'cuadrante' => 'Estructura'],
                ['tipo' => TipoBloque::Formula, 'tiempo' => '7–14 min', 'titulo' => "⭐ Fórmula: {$f}", 'detalle' => 'Fórmula completa en grupo, luego segmentos por tiempo (30 seg c/u). Regular: hasta la mitad. Leadership: completa.', 'cuadrante' => 'Memorización / Balance'],
                ['tipo' => TipoBloque::DefensaYAtaque, 'tiempo' => '14–21 min', 'titulo' => '🥋 Defensa y Ataque', 'detalle' => "{$d} – Parejas con roles definidos (ataca/defiende). Luego con paleta o escudo. Énfasis en control de distancia.", 'cuadrante' => 'Control / Velocidad'],
                ['tipo' => TipoBloque::Armas, 'tiempo' => '21–27 min', 'titulo' => '🏹 Armas SJB/BME', 'detalle' => 'Fórmula simple supervisada. Ángulos y movimientos específicos. Leadership: también dobles.', 'cuadrante' => 'Coordinación / Intensidad'],
                ['tipo' => TipoBloque::Patadas, 'tiempo' => '27–34 min', 'titulo' => '🦵 Patadas y Combinaciones', 'detalle' => "Patadas: {$p}. 4 etapas con paleta en parejas.\nCombinaciones: {$combos}. Practicar cada combo x4 alternando piernas.", 'cuadrante' => 'Clavado / Látigo'],
                ['tipo' => TipoBloque::Roturas, 'tiempo' => '34–37 min', 'titulo' => '💥 Roturas', 'detalle' => "{$r}. 1–2 técnicas. Foam o plástico según cinturón. Compañeros evalúan.", 'cuadrante' => 'Precisión / Foco'],
                ['tipo' => TipoBloque::CombatWeapon, 'tiempo' => '37–42 min', 'titulo' => '⚔️ Combat Weapon + Sparring', 'detalle' => 'Movilidad 1,2,3 golpes afuera/adentro 3 zonas. Sparring al punto (2 min) con rotación de parejas.', 'cuadrante' => 'Timing / Amagues'],
                ['tipo' => TipoBloque::AnunciosPremios, 'tiempo' => '42–45 min', 'titulo' => '🏆 Anuncios y Premios', 'detalle' => 'Reconocimiento del alumno destacado. Nominaciones a examen. Anuncio de torneos.', 'cuadrante' => 'Legado'],
            ],
            GrupoEtario::JovenesAdultos => [
                ['tipo' => TipoBloque::Calentamiento, 'tiempo' => '0–8 min', 'titulo' => '🔥 Warm Up', 'detalle' => 'Cambios de guardia, movilidad articular completa, desplazamientos en sparring, sentadillas y puños, levantamiento de pierna recto y lateral, rodillas circulares adentro/afuera, puños + patadas + burpee.', 'cuadrante' => 'Estructura'],
                ['tipo' => TipoBloque::Formula, 'tiempo' => '8–15 min', 'titulo' => "⭐ Fórmula: {$f}", 'detalle' => 'Fórmula completa. Énfasis en torsión/clavado y cadera/posiciones. Grupal → individual con autoevaluación. Leadership: autocorrección de postura.', 'cuadrante' => 'Torsión / Cadera'],
                ['tipo' => TipoBloque::DefensaYAtaque, 'tiempo' => '15–22 min', 'titulo' => '🥋 Defensa y Ataque', 'detalle' => "{$d} – Libre en parejas con velocidad real. Ataque–defensa–contraataque. Con paleta o escudo libre.", 'cuadrante' => 'Velocidad / Foco'],
                ['tipo' => TipoBloque::Armas, 'tiempo' => '22–28 min', 'titulo' => '🏹 Armas SJB/BME (Completa)', 'detalle' => 'Fórmula completa, ángulos, movimientos específicos. Segmentos cronometrados. Coordinación → Velocidad → Intensidad → Poder.', 'cuadrante' => 'Coordinación / Poder'],
                ['tipo' => TipoBloque::Patadas, 'tiempo' => '28–35 min', 'titulo' => '🦵 Patadas y Combinaciones', 'detalle' => "Patadas: {$p}. 4 etapas completas con compañero.\nCombinaciones: {$combos}. Cada combo x6 con corrección técnica del instructor.", 'cuadrante' => 'Pivot / Postura'],
                ['tipo' => TipoBloque::Roturas, 'tiempo' => '35–38 min', 'titulo' => '💥 Roturas', 'detalle' => "{$r}. Tablillas reales según nivel. Velocidad de ejecución y distancia correcta.", 'cuadrante' => 'Velocidad / Distancia'],
                ['tipo' => TipoBloque::CombatWeapon, 'tiempo' => '38–43 min', 'titulo' => '⚔️ Combat Weapon + Sparring', 'detalle' => 'Movilidad 1,2,3 golpes afuera/adentro 3 zonas. Sparring continuo, de examen o exhibición según semana.', 'cuadrante' => 'Timing / Ataques y Defensas'],
                ['tipo' => TipoBloque::AnunciosPremios, 'tiempo' => '43–45 min', 'titulo' => '🏆 Anuncios', 'detalle' => 'Nominación a exámenes. Retroalimentación técnica individual breve.', 'cuadrante' => 'Legado'],
            ],
        };
    }

    /**
     * Colores de las etiquetas de cuadrante (del prototipo: cuadColor).
     */
    private const CUAD_COLOR = [
        'Estructura' => '#0077b6',
        'Memorización / Emoción' => '#0096c7',
        'Memorización' => '#0096c7',
        'Memorización / Balance' => '#0096c7',
        'Torsión / Cadera' => '#0096c7',
        'Postura / Equilibrio' => '#e76f51',
        'Control / Velocidad' => '#48cae4',
        'Velocidad / Foco' => '#48cae4',
        'Velocidad / Reacción' => '#48cae4',
        'Coordinación / Intensidad' => '#f77f00',
        'Coordinación / Poder' => '#f77f00',
        'Coordinación' => '#f77f00',
        'Clavado / Látigo' => '#d62828',
        'Pivot / Postura' => '#d62828',
        'Precisión / Foco' => '#7b2d8b',
        'Foco / Confianza' => '#9b59b6',
        'Velocidad / Distancia' => '#7b2d8b',
        'Timing / Amagues' => '#2d6a4f',
        'Timing / Ataques y Defensas' => '#2d6a4f',
        'Legado' => '#6c757d',
    ];
}
