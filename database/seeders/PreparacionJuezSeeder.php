<?php

namespace Database\Seeders;

use App\Models\Academia;
use App\Models\Contenido;
use App\Models\Nivel;
use App\Support\Tenancy\Academia as Tenant;
use Illuminate\Database\Seeder;

/**
 * Contenido de "Aprender" (LMS): preparación para el examen de Juez ATA.
 *
 * Material de estudio de la certificación de jueces BEKHO Chile (ATA), basado en
 * el "Manual del Juez" (reglamento ATA 2025-2026) generado para la federación:
 *   - Manual de referencia en 18 secciones (todo el reglamento del juez).
 *   - Cuestionarios de práctica con clave de respuestas y explicación, por
 *     nivel (1, 2, 3 y un repaso de Puntuación/Combate).
 *   - Los documentos oficiales en Google Drive.
 *
 * El LMS solo maneja texto/video/documento, así que el cuestionario interactivo
 * del prototipo se vuelca como texto de estudio (pregunta + respuesta correcta +
 * porqué). Aprobación mínima del examen real: 80%.
 *
 * Idempotente (updateOrCreate).
 */
class PreparacionJuezSeeder extends Seeder
{
    /** PDF oficial de estudio (prueba juez N1/N2/N3) en Google Drive. */
    private const URL_PDF_ESTUDIO = 'https://drive.google.com/file/d/123kXlGHNylSZj-OOLGAx0b0zc_VnOBEZ/view';

    /** Reglas del torneo ATA 2025-2026 (español) en Google Drive. */
    private const URL_REGLAS = 'https://drive.google.com/file/d/17O107pH6nOWveJvsUAuZAG0bc3llCXRt/view';

    public function run(): void
    {
        $academia = Academia::where('nombre', 'BEKHO Power Academy')->first();

        if (! $academia) {
            $this->command?->warn('No existe la academia BEKHO; ejecuta antes RolesPermisosSeeder.');

            return;
        }

        Tenant::set($academia->id);

        $nivel = Nivel::updateOrCreate(
            ['academia_id' => $academia->id, 'nombre' => 'Preparación para examen de juez nivel 1'],
            [
                'descripcion' => 'Manual del Juez ATA (reglamento 2025-2026) y práctica de examen para la '
                    .'certificación de jueces BEKHO Chile. Incluye el reglamento por secciones y cuestionarios '
                    .'con respuestas para Niveles 1, 2 y 3. Aprobación mínima: 80%.',
                'orden' => 10,
                'activo' => true,
            ],
        );

        $orden = 0;
        foreach ($this->contenidos() as $c) {
            Contenido::updateOrCreate(
                ['nivel_id' => $nivel->id, 'titulo' => $c['titulo']],
                [
                    'academia_id' => $academia->id,
                    'descripcion' => $c['descripcion'] ?? null,
                    'tipo' => $c['tipo'],
                    'cuerpo' => $c['cuerpo'] ?? null,
                    'url_recurso' => $c['url_recurso'] ?? null,
                    'orden' => $orden++,
                    'activo' => true,
                ],
            );
        }

        Tenant::olvidar();
    }

    /**
     * @return list<array{titulo: string, tipo: string, descripcion?: string, cuerpo?: string, url_recurso?: string}>
     */
    private function contenidos(): array
    {
        $items = [];

        $items[] = [
            'titulo' => 'Cómo usar este material',
            'tipo' => 'texto',
            'descripcion' => 'Guía de estudio del reglamento ATA y práctica de examen.',
            'cuerpo' => <<<'TXT'
Esta es la guía de estudio para la certificación de Juez ATA (BEKHO Chile), basada en el reglamento ATA 2025-2026.

Cómo estudiar:
1. Lee el "Manual de referencia" (18 secciones): cubre todo lo que un juez debe saber, de los niveles de certificación al arbitraje de cada modalidad.
2. Resuelve los cuestionarios de práctica. Cada uno trae la respuesta correcta y el porqué. Empieza por el Nivel 1.
3. Repasa el bloque de "Puntuación y combate" y los documentos oficiales adjuntos.

Datos de la certificación:
• Aprobación mínima: 80%.
• Cada certificación dura 1 año; solo se obtiene un nivel de galón cada 30 días.
• Nivel 1 = galón azul (esquina en color) · Nivel 2 = galón rojo · Nivel 3 = galón negro (requisito para rendir 4º grado y superior).

Importante: algunas preguntas del formulario histórico son ambiguas o dependen de la versión chilena del examen. Están marcadas con "⚠ Verifica con tu instructor". Ante cualquier duda, el reglamento oficial y tu instructor jefe mandan.
TXT,
        ];

        // --- Manual de referencia (18 secciones) --------------------------------
        foreach ($this->manual() as $s) {
            $cuerpo = implode("\n\n", array_map(fn (string $p) => '• '.$p, $s['points']));

            $items[] = [
                'titulo' => 'Manual · '.$s['tag'].' · '.$s['title'],
                'tipo' => 'texto',
                'descripcion' => 'Reglamento ATA 2025-2026 — sección '.$s['tag'].'.',
                'cuerpo' => $cuerpo,
            ];
        }

        // --- Cuestionarios de práctica por nivel --------------------------------
        $quiz = $this->quiz();

        $items[] = [
            'titulo' => 'Cuestionario · Nivel 1 (con respuestas)',
            'tipo' => 'texto',
            'descripcion' => 'Práctica de examen de Juez Nivel 1.',
            'cuerpo' => $this->formatearQuiz($quiz[1]),
        ];
        $items[] = [
            'titulo' => 'Cuestionario · Nivel 2 (con respuestas)',
            'tipo' => 'texto',
            'descripcion' => 'Práctica de examen de Juez Nivel 2.',
            'cuerpo' => $this->formatearQuiz($quiz[2]),
        ];
        $items[] = [
            'titulo' => 'Cuestionario · Nivel 3 (con respuestas)',
            'tipo' => 'texto',
            'descripcion' => 'Práctica de examen de Juez Nivel 3.',
            'cuerpo' => $this->formatearQuiz($quiz[3]),
        ];
        $items[] = [
            'titulo' => 'Repaso · Puntuación y combate (con respuestas)',
            'tipo' => 'texto',
            'descripcion' => 'Ejercicios de puntuación de fórmula, sparring y combat weapons.',
            'cuerpo' => $this->formatearQuiz($quiz[4]),
        ];

        // --- Documentos oficiales ------------------------------------------------
        $items[] = [
            'titulo' => 'Documento: prueba de juez N1/N2/N3 (estudio)',
            'tipo' => 'documento',
            'descripcion' => 'Formulario oficial de estudio (Google Drive).',
            'url_recurso' => self::URL_PDF_ESTUDIO,
        ];
        $items[] = [
            'titulo' => 'Documento: Reglas del Torneo ATA 2025-2026',
            'tipo' => 'documento',
            'descripcion' => 'Reglamento oficial de referencia (Google Drive).',
            'url_recurso' => self::URL_REGLAS,
        ];

        return $items;
    }

    /**
     * Convierte una lista de preguntas en texto de estudio (pregunta, opciones,
     * respuesta correcta y explicación).
     *
     * @param  list<array{n: int, q: string, options: list<string>, correct: int, why: string, flag?: bool}>  $preguntas
     */
    private function formatearQuiz(array $preguntas): string
    {
        $bloques = [];
        $i = 1;

        foreach ($preguntas as $p) {
            $lineas = [$i.'. '.$p['q']];

            foreach ($p['options'] as $j => $opcion) {
                $letra = chr(ord('a') + $j);
                $lineas[] = '   '.$letra.') '.$opcion;
            }

            $letraCorrecta = chr(ord('a') + $p['correct']);
            $lineas[] = '   ✔ Respuesta: '.$letraCorrecta.') '.$p['options'][$p['correct']];
            $lineas[] = '   Por qué: '.$p['why'];

            if (! empty($p['flag'])) {
                $lineas[] = '   ⚠ Verifica con tu instructor (pregunta ambigua o dependiente de la versión del examen).';
            }

            $bloques[] = implode("\n", $lineas);
            $i++;
        }

        return implode("\n\n", $bloques);
    }

    /**
     * Manual de referencia: el reglamento del juez ATA en 18 secciones.
     *
     * @return list<array{tag: string, title: string, points: list<string>}>
     */
    private function manual(): array
    {
        return [
            ['tag' => '01', 'title' => 'Niveles de juez y certificación', 'points' => [
                'Nivel 1 (galón azul): Juez de Esquina solo en cinturones de color. Mínimo 14 años, 1er grado decidido o superior.',
                'Nivel 2 (galón rojo): Esquina o Central en cinturones de color; Esquina en cinturón negro hasta su rango. Mínimo 16 años.',
                'Nivel 3 (galón negro): Esquina/Central en color; Esquina en negro hasta su rango; Central en negro hasta un rango menor al suyo. Mínimo 18 años, 2do grado o superior.',
                'Todas las certificaciones duran 1 año. Se aprueba con 80%. Solo se obtiene un nivel de galón cada 30 días. Nivel 3 es requisito para rendir 4to grado y superior.',
            ]],
            ['tag' => '02', 'title' => 'Oficiales del torneo y cadena de mando', 'points' => [
                'Cadena: hablar con el juez → instructor → RTTL → Departamento de Torneos en la Sede.',
                'El Director Internacional de Torneos es el árbitro final de las reglas.',
                'El RTTL (Líder Regional) maneja sanciones, divisiones, clínicas y certificaciones de jueces.',
                'Ningún superior/maestro de una región puede arbitrar un fallo en un ring; solo puede pedir que se detenga para que llegue el Árbitro.',
            ]],
            ['tag' => '03', 'title' => 'Uniformes y código de vestimenta', 'points' => [
                'Dobok blanco tradicional con parche ATA para tradicional, armas, sparring y combat weapons.',
                'Uniforme negro Creative/Xtreme solo para esos eventos (no para tradicional). Adidas negro puede acortarse a mitad del antebrazo.',
                'Cinturones negros presentes deben vestir dobok hasta la despedida; no se permite ropa de calle ni chaquetas sobre el dobok.',
                'Sin joyas durante la competencia (salvo argolla con cinta o piedra a la palma, brazalete médico y medalla religiosa).',
            ]],
            ['tag' => '04', 'title' => 'Equipo de seguridad y calzado del juez', 'points' => [
                'Obligatorio en sparring y combat weapons: guantines, botines, casco con careta, peto con logo ATA, protector bucal y coquilla (varones, sin excepción de edad).',
                'Cinturón de color: equipo rojo o negro. Cinturón negro: equipo negro. El azul NO está aprobado. Manos/pies/cabeza deben coincidir en color.',
                'Combat weapons: misma exigencia que sparring, salvo guantes (tradicionales o de combate marca ATA). La careta Champion no se permite en combat weapons.',
                'Jueces con dobok: descalzos o zapatillas deportivas (predominantemente blancas o negras). Las sandalias ATA NO sirven para jueces.',
            ]],
            ['tag' => '05', 'title' => 'Armas: tipos y restricciones', 'points' => [
                'Armas ATA: Bahng Mahng Ee, Ssahng Jeol Bong, Jahng Bong, Ssahng Nat (solo doble), Jee Pahng Ee, Gum Do, Oh Sung Do, Sam Dan Bong.',
                'Cinturón de color: solo armas de seguridad Protech (excepto Jahng Bong y Jee Pahng Ee). No se permiten armas de madera ni de metal.',
                'Tradicional: el arma NO puede estar decorada ni alterada. En Creative/Xtreme sí puede decorarse (no alterarse).',
                'Inspección de armas obligatoria antes de cada evento de armas; el competidor debe competir con el arma inspeccionada.',
            ]],
            ['tag' => '06', 'title' => 'Fórmulas tradicionales: roles y puntuación', 'points' => [
                'Juez A (izquierda): solo posturas y patadas. Juez B (derecha): solo técnicas de mano y bloqueos. Central: presentación general.',
                'Puntaje 1–9: 9 = mejor del grupo · 6–8 = sobre el promedio · 5 = promedio · 1–4 = bajo el promedio · 0 = incompleta (solo Central).',
                'Los primeros 3 competidores presentan antes de recibir nota. La nota es comparativa entre los del ring ese día.',
                'Sin límite de tiempo ni de perímetro: no se penaliza salir del área ni ajustar la posición por un obstáculo. Una sola oportunidad, no se repite.',
            ]],
            ['tag' => '07', 'title' => 'Fórmula incompleta y empates', 'points' => [
                'Incompleta = omitir al menos 4 movimientos consecutivos o detenerse y no continuar. Solo el Central da 0; los de esquina puntúan normal.',
                'NO es incompleta: olvidar 1–2 técnicas, girar mal o hacer una técnica incorrecta (el Central puede descontar a criterio).',
                'Empate (4+ competidores): repiten la MISMA fórmula individualmente; los 3 jueces evalúan la fórmula completa y apuntan al mejor.',
                '2–3 competidores: no se dan notas; cada juez apunta su elección según su rol. Se registra 1°=9, 2°=8, 3°=7. Si los 3 apuntan distinto, se va a desempate (sin repetir).',
            ]],
            ['tag' => '08', 'title' => 'Armas tradicionales', 'points' => [
                'Todos los jueces evalúan la ejecución completa; solo el Central considera si está incompleta.',
                'Control del arma = criterio #1, sobre todo en desempates (quien deja caer no gana sobre quien controla).',
                'Caída del arma: −1 punto de cada juez por cada caída. Recoger mal (sin una rodilla al suelo y ambas manos): −1 punto.',
                'Arma rota: sin deducción; 30 segundos para reemplazarla. Tigres/Recreativo: rutina libre de 30 seg sin caídas ni gimnasia.',
            ]],
            ['tag' => '09', 'title' => 'One-Steps tradicionales', 'points' => [
                'Para cinturones blanco, naranja y amarillo. El rojo inicia siempre con bloqueo bajo.',
                'Máximo 3 pasos por combate; se usan one-steps de blanco/naranja/amarillo (pasos 1 y 2, en cualquier orden; el 3 no).',
                'Gana quien llega a 2 puntos. Deben hacerse dos combinaciones distintas en los dos primeros intentos para ganar ambos.',
                'Criterio en orden: calidad de técnica básica → potencia → fluidez → cercanía al objetivo → actitud.',
            ]],
            ['tag' => '10', 'title' => 'Combat Weapons (combate con armas)', 'points' => [
                'Todos los rangos elegibles. Gana quien llega a 10 puntos o tiene más en 2 minutos. Único arma aprobada: Protech Combat Bahng Mahng Ee.',
                'Puntos: 1 al cuerpo · 2 a cabeza, brazo armado bajo el codo o estocada al muslo frontal · +1 por técnica con salto (ambos pies en el aire).',
                'Caída del arma = punto para el oponente. Si la caída es parte de la acción de punto, no recibe su punto; si es posterior, sí lo recibe.',
                'Advertencia sin contacto: 1ra verbal, luego +1 al rival. Con contacto: 1ra +1 al rival, 2da descalificación.',
            ]],
            ['tag' => '11', 'title' => 'Point Sparring: señales y puntuación', 'points' => [
                'Mínimo: cronómetro, 3 jueces y planillero. Rondas de 2 min; gana quien llega a 5 puntos o tiene más al final.',
                'Mano al torso frontal = 1 pt. Pie al cuerpo = 1 pt. Pie a la cabeza = 2 pts. Pie con salto al cuerpo = 2 pts. Pie con salto a la cabeza = 3 pts.',
                'Señales: Punto / No punto (vio pero no puntúa) / No visto (no lo vio, sale de la votación) / Advertencia. No se da advertencia y punto a la vez.',
                'Victoria súbita tras empate: primer punto gana, sin límite de tiempo. Mayoría define; el voto del Central vale igual que los demás.',
            ]],
            ['tag' => '12', 'title' => 'Sparring: advertencias y resolución de puntos', 'points' => [
                'Se vota simultáneamente cuando el Central detiene el combate y pide verificación. Dos "No visto" no anulan un punto: decide la mayoría restante.',
                'Si un juez apunta al competidor correcto pero levanta la bandera del color equivocado, puede corregir el color.',
                'Sin contacto: 1ra verbal, luego punto al rival. Con contacto: 1ra punto al rival, 2da descalificación.',
                'Regla de "no culpa": si por acción ajena el golpe llega a zona ilegal, no se penaliza. Contacto excesivo: a criterio del Central (punto o descalificación).',
            ]],
            ['tag' => '13', 'title' => 'Creative y Xtreme', 'points' => [
                'Para competir en Creative/Xtreme hay que competir también en el evento tradicional correspondiente.',
                'Máximo 2 minutos, sin mínimo. Al menos 50% material original. Música opcional (aprobada por el instructor).',
                'Creative: NO se permite lanzar el arma ni gimnasia (volteretas, ruedas, giros +360°). Xtreme: SÍ se permiten lanzamientos del arma.',
                'Ya NO hay descalificación por infracción: el Central da 0 y los de esquina puntúan normal. Caída del arma: −1 de los 3 jueces; mínimo 1 punto.',
            ]],
            ['tag' => '14', 'title' => 'Sparring por equipos / Sync / Demo', 'points' => [
                'Equipos regionales: 3 competidores (1 mujer, 2 hombres) del mismo estado. Combate de 1:30 por pelea; 3 peleas por encuentro.',
                'Coaching (entrenador) solo se permite en sparring/combat weapons por equipos.',
                'Decisión superior (punto extra): diferencia de 8+ en sparring u 11+ en combat weapons.',
                'Team Demo: 4–30 miembros del mismo estado, máx 3 min. Team Sync: 2–3 miembros, máx 2 min. Solo armas/tablas aprobadas.',
            ]],
            ['tag' => '15', 'title' => 'ATA Tiger (3–6 años)', 'points' => [
                'Es una introducción a la competencia: no hay perdedores ni descalificaciones. Solo 1 juez y 1 ayudante por ring.',
                'No hay puntaje numérico: el juez hace un comentario positivo. Cada Tiger hace dos rondas de one-steps o combate libre.',
                'En sparring no se dan puntos; el juez dice "break" y comenta lo positivo. Rondas de 1 minuto.',
                'Premios por categorías (mejores patadas, gritos más fuertes, mejor actitud, etc.). Niños y niñas pueden compartir ring.',
            ]],
            ['tag' => '16', 'title' => 'Habilidades Especiales', 'points' => [
                'Requiere aprobación previa del Comité de Elegibilidad. Categorías: física, cognitiva o espectro autista (esta se renueva cada año).',
                'Forma/armas: todos los jueces evalúan la forma completa y la intención de la técnica, no las asignaciones originales.',
                'Sparring cognitivo/físico: toda técnica con puntuación vale 1 punto (para nivelar). Autismo: reglas de puntos estándar.',
                'Los puntos ganados antes de la aprobación del comité se pierden.',
            ]],
            ['tag' => '17', 'title' => 'Clasificaciones y puntos de torneo', 'points' => [
                'Clase C (interno): 3-2-1 (5+ competidores). Clase B: 5-3-1. Clase A: 8-5-2. Clase AA (Nacional/Panamericano): 15-10-8. Clase AAA (Super-20): 20-15-10.',
                'Clase A y superiores otorgan puntos completos sin importar cuántos competidores haya en el ring.',
                'Máximo de la temporada: 99 puntos (1 Super-20 + 2 nacionales + 5 regionales + 3 Clase C).',
                'Edad de competencia: se fija por la edad al 31 de diciembre de la temporada. Membresía ATA debe estar vigente.',
            ]],
            ['tag' => '18', 'title' => 'Procedimientos clave del ring', 'points' => [
                'El Central recoge tarjetas y verifica que el número de competidores coincida; confirma edad y división; presenta a los jueces.',
                'El Central NO calienta físicamente a los competidores. No puede alterar ni modificar reglas (consulta al RTTL).',
                'Llegada tarde: se acepta si la modalidad no se ha cerrado; el competidor insertado compite último, sin tiempo extra de calentamiento.',
                'Lesión de menor (media): para continuar requiere autorización del equipo médico primero y luego del instructor/padres si están presentes.',
            ]],
        ];
    }

    /**
     * Cuestionarios de práctica por nivel (1, 2, 3 y 4 = repaso puntuación/combate).
     *
     * @return array<int, list<array{n: int, q: string, options: list<string>, correct: int, why: string, flag?: bool}>>
     */
    private function quiz(): array
    {
        return [
            1 => [
                ['n' => 7, 'q' => '¿Cuál es el objetivo de la evaluación en la competencia de fórmulas?', 'options' => ['Evaluar la técnica universal considerando diferencias por escuela/región.', 'Evaluar habilidad en la ejecución de técnicas y movimientos.', 'Evaluar coordinación, concentración y fuerza.', 'Todas las anteriores.', 'B y C.'], 'correct' => 3, 'why' => 'Contempla los tres aspectos: técnica universal, habilidad de ejecución y atributos como coordinación y fuerza.'],
                ['n' => 8, 'q' => 'Para la evaluación de fórmula son necesarios:', 'options' => ['2 Jueces y un planillero.', '3 Jueces y cronómetro.', '3 Jueces y un planillero.', '4 Jueces y un planillero.', 'Sólo 2 Jueces.'], 'correct' => 2, 'why' => 'Fórmula: 3 jueces y un planillero. No lleva cronómetro (sin límite de tiempo en campeón).'],
                ['n' => 9, 'q' => '¿Qué característica NO tiene que ver con el Juez Central?', 'options' => ['Tiene autoridad sobre TODOS los competidores de su categoría.', 'Controlar que se cumplan las reglas.', 'Puede descalificar a un competidor por sí solo.', 'Hacer llegar los resultados a la mesa central.', 'A y D.'], 'correct' => 2, 'why' => 'Ningún juez descalifica por sí solo ni anula a otro: todos los votos pesan igual. Los problemas serios van al RTTL.'],
                ['n' => 10, 'q' => 'Indique qué evaluación NO está correcta:', 'options' => ['9.9 el mejor del grupo.', '9.8–9.6 mejor que el promedio.', '9.5 el promedio.', '9.4–9.1 debajo del promedio.', '9.0 fórmula incompleta, la colocan los tres jueces.'], 'correct' => 4, 'why' => 'Falsa: en fórmula incompleta SOLO el Central otorga el 9.0/0; los de esquina evalúan normal.'],
                ['n' => 11, 'q' => 'Indique la alternativa que está correcta:', 'options' => ['Si son 3 competidores, salen los tres a presentar y luego se colocan las notas según los criterios.', 'En Creative Weapons se permite lanzar el arma.', 'En Xtreme fórmula se descuenta 1 pt de los tres jueces si pasa de 2 min.', 'En armas tradicionales se descuenta 1 pt si rompe y consigue otra en 30 seg.', 'Con 3 en fórmula tradicional, el 2do lugar siempre obtiene 8-8-8.'], 'correct' => 0, 'flag' => true, 'why' => 'Correcta la (a). En Creative no se lanza el arma; el arma rota no se penaliza; en Xtreme la sanción es 0 del central. Ojo: la opción (e) también puede darse por válida en el sistema antiguo.'],
                ['n' => 12, 'q' => 'Fórmula incompleta — puntaje de los Jueces de esquina:', 'options' => ['9.0', '9.5', '9.0 ó 9.1', '9.1 a 9.9', '9.0 a 9.5'], 'correct' => 3, 'why' => 'Los de esquina puntúan normal (9.1–9.9). El 9.0/0 lo pone solo el Central.'],
                ['n' => 13, 'q' => '¿Cuántas oportunidades para hacer la fórmula hay?', 'options' => ['1 vez para todos los grados.', '2 para Cinturones Negros con autorización.', '1 leadership y 1 Cinturón Negro.', '1 Negro y 2 Color.', '1 Negro (excepto infantil) y 2 Color.'], 'correct' => 0, 'why' => 'Una sola oportunidad para todos. La fórmula no se repite en ninguna categoría.'],
                ['n' => 14, 'q' => 'Para la evaluación de Sparring debe haber como mínimo:', 'options' => ['2 Jueces, planillero y cronómetro.', 'Un cronómetro, 3 Jueces y un planillero.', 'Un Juez y un planillero.', 'Sólo 3 Jueces.', 'Un central, un esquina, dos asistentes.'], 'correct' => 1, 'why' => 'Sparring: cronómetro, 3 jueces y planillero (a diferencia de fórmula, que no usa cronómetro).'],
                ['n' => 15, 'q' => "¿Qué se entiende por 'Victoria Súbita'?", 'options' => ['Ganar dentro del tiempo reglamentario.', 'El competidor se lesiona y no puede continuar.', "Ganar en tiempo extra 'a criterio' de los jueces.", 'Ganar después de un empate, en un tiempo extra.', 'B y C.'], 'correct' => 3, 'why' => 'Tras un empate, en tiempo extra el primer punto gana, sin límite de tiempo.'],
                ['n' => 16, 'q' => '¿En qué momento los Jueces de esquina dan sus puntajes?', 'options' => ['En cualquier momento.', 'Cuando el combate está detenido y el Central lo pide.', 'Cuando ven puntos y detienen el combate.', 'Cuando ven mala intención.', 'En cualquier momento tras llamar a punto.'], 'correct' => 1, 'why' => 'Votan simultáneamente cuando el Central detiene el combate y pide la verificación.'],
            ],
            2 => [
                ['n' => 17, 'q' => 'Un Juez Nivel 2 está certificado para cubrir ¿cuál posición?', 'options' => ['Central en Cinturones de Color.', 'Esquina en Cinturones Negros.', 'Esquina en Cinturones de Color.', 'Todas.'], 'correct' => 3, 'why' => 'Nivel 2: esquina o central en color, y esquina en negro hasta su rango. Todas aplican.'],
                ['n' => 18, 'q' => 'Las responsabilidades del Juez Central son:', 'options' => ['Velar por que cada competidor siga las reglas.', 'Controlar su ring durante la competencia.', 'Controlar que los resultados se traspasen a la planilla.', 'Todas las anteriores.'], 'correct' => 3, 'why' => 'El Central cumple las tres funciones.'],
                ['n' => 19, 'q' => 'Una vez recibida la categoría asignada, el Juez Central debe:', 'options' => ['Revisar que todos sean del mismo grado y rango de edad.', 'Dejar la categoría haciendo calentamiento.', 'Supervisar que estén las planillas necesarias.', 'A y C.'], 'correct' => 3, 'why' => 'Confirma grado/edad y supervisa planillas. NO debe calentar físicamente a los competidores.'],
                ['n' => 20, 'q' => '¿Cuál es el orden de las competencias?', 'options' => ['Fórmula, Armas, Sparring.', 'Fórmula, Armas, Sparring, Combat Weapons.', 'Xtreme, Fórmula, Sparring, Armas, CW.', 'Fórmula, Armas, Combat Weapons, Sparring, Creative, Xtreme.'], 'correct' => 3, 'why' => 'Orden oficial: forma → armas → combat weapons → sparring → creative → xtreme.'],
                ['n' => 21, 'q' => 'Si un competidor llega tarde a su pista asignada, lo correcto es:', 'options' => ['Entra a fórmula mientras no se presente el último competidor.', 'Entra a combat weapons mientras no corra el 2do encuentro de 1ra ronda.', 'Entra a fórmula mientras no se llame al 1er competidor de armas.', 'Si quedan 3 por presentar en fórmula, se acepta y presenta en último lugar.'], 'correct' => 3, 'why' => 'Si la modalidad no se ha cerrado, se acepta e ingresa para competir de último, sin tiempo extra.'],
                ['n' => 22, 'q' => 'El Juez Central en fórmulas evaluará:', 'options' => ['Patadas y posiciones.', 'Básicos y Defensas.', 'Memoria y actitud.', 'Todas.'], 'correct' => 3, 'why' => 'El Central evalúa la presentación general, que incluye todos los criterios.'],
                ['n' => 23, 'q' => 'Un Cinturón de Color, ¿cuántas veces puede repetir su fórmula?', 'options' => ['Dos veces.', 'Cuantas necesite si es Tiny Tiger.', 'Una vez.', 'Ninguna.'], 'correct' => 3, 'why' => 'Se presenta una sola vez: no se repite (cero repeticiones) en ninguna categoría.'],
                ['n' => 24, 'q' => 'Indique la alternativa que está correcta:', 'options' => ['Si son 3 competidores, salen los tres a presentar y luego se colocan las notas.', 'En Creative se permite lanzar el arma.', 'En Xtreme se descuenta 1 pt si pasa de 2 min.', 'En armas se descuenta 1 pt si rompe y consigue otra en 30 seg.', 'Con 3 en fórmula, el 2do siempre obtiene 8-8-8.'], 'correct' => 0, 'flag' => true, 'why' => 'Correcta la (a). La (e) puede ser válida en el sistema antiguo.'],
                ['n' => 25, 'q' => 'Si un competidor se salta un paso en su fórmula, los Jueces de Esquina deben:', 'options' => ['Ignorar el hecho, bajando el puntaje máximo.', 'Advertir y hacer que la presente de nuevo.', 'Bajar el puntaje.', 'No hacer nada.'], 'correct' => 3, 'why' => 'Los de esquina solo juzgan las técnicas mostradas; descontar por omisiones es tarea del Central.'],
                ['n' => 26, 'q' => 'Una fórmula incompleta se define como:', 'options' => ['Fórmula hecha sin gritos.', 'Olvidar o saltarse 2 movimientos.', 'No terminar donde comenzó.', 'Omitir al menos 4 movimientos consecutivos.'], 'correct' => 3, 'why' => 'Incompleta = omitir 4+ movimientos consecutivos o detenerse y no continuar.'],
                ['n' => 27, 'q' => 'Si un competidor sale del perímetro de competencia (fórmula):', 'options' => ['El Central descuenta puntos.', 'Los de esquina descuentan puntos.', 'No se descuentan puntos.', 'Dos salidas y el Central descuenta.'], 'correct' => 2, 'why' => 'No hay restricción de límites en fórmula: no se penaliza salir del área.'],
                ['n' => 28, 'q' => 'El Central pide puntajes en una fórmula no muy buena y los de esquina dan notas muy altas. ¿Qué hace?', 'options' => ['Manifestar acuerdo/desacuerdo verbal con los puntajes.', 'Asegurar que el planillero escriba los puntajes correctos.', 'Escribir en Observaciones su desacuerdo con los de esquina.', 'Ninguna de las anteriores.'], 'correct' => 3, 'flag' => true, 'why' => 'Cada juez puntúa su área de forma independiente; el Central no interfiere en los puntajes de esquina.'],
                ['n' => 29, 'q' => 'Terminada la competencia de fórmulas, el Juez Central debe:', 'options' => ['Confirmar que todos se hayan presentado.', 'Verificar la puntuación en la planilla.', 'Verificar empates.', 'Todas.'], 'correct' => 3, 'why' => 'Confirma presentaciones, verifica puntajes y revisa empates.'],
                ['n' => 30, 'q' => '¿Cuál de las siguientes acciones está permitida?', 'options' => ['Enrollar las mangas del dobok a la altura del codo.', 'Las puntas del cinturón pueden guardarse siguiendo la línea del mismo.', 'Enrollar las mangas a la altura del antebrazo.', 'Ninguna.'], 'correct' => 1, 'flag' => true, 'why' => 'Las puntas del cinturón pueden acomodarse siguiendo su línea.'],
                ['n' => 31, 'q' => 'Si un competidor en Xtreme Formula se pasa del tiempo reglamentario:', 'options' => ['El puntaje debe ser rebajado.', 'El central otorga 0 y los de esquina puntúan normal.', 'No ajustar por tiempo sino por movimientos.', 'No podrá recibir puntaje por pasarse del tiempo.'], 'correct' => 1, 'why' => 'Hoy no hay descalificación por tiempo: el Central da 0 y los de esquina puntúan normal.'],
                ['n' => 32, 'q' => 'En la competencia de Tigers en Chile, indique la alternativa INCORRECTA:', 'options' => ['En fórmula los tres jueces muestran pulgares arriba y el central felicita.', 'En armas tradicional puede hacer una fórmula libre de 30 seg.', 'En Sparring se marca alternadamente rojo/blanco para que empaten y ambos ganan.', 'En Combat Weapons arbitran normal pero el marcador queda en cero y ambos ganan.', 'Se le da una medalla por fórmula-Sparring y otra por cada competencia extra.'], 'correct' => 2, 'flag' => true, 'why' => 'Falsa: en Tigers el sparring NO se puntúa; el juez comenta lo positivo. Detalles específicos de Chile.'],
                ['n' => 33, 'q' => 'La principal consideración al evaluar armas debe ser:', 'options' => ['Presentación general del material y manejo por ambos lados.', 'Calidad de las patadas.', 'Tiempo y fluidez de movimientos del arma.', 'A y C.'], 'correct' => 3, 'why' => 'Se evalúa la presentación/manejo y el tiempo-fluidez (control del arma es el criterio rector).'],
                ['n' => 34, 'q' => 'Si el arma se cae:', 'options' => ['Se descuenta 1 pt por cada caída.', 'Se detiene y se comienza de nuevo.', 'Solo el Central baja el puntaje por mala precisión.', 'Se evalúa con 9.1 en cinturones negros.'], 'correct' => 0, 'why' => 'Todos los jueces descuentan 1 punto por cada caída del arma en armas tradicionales.'],
                ['n' => 35, 'q' => 'El rol principal del Juez Central durante la competencia es:', 'options' => ['Determinar un ganador.', 'Cuidar la integridad de todos los competidores.', 'Determinar puntos claros.', 'Dejar contento al público.'], 'correct' => 1, 'why' => 'La seguridad/integridad de los competidores es la prioridad del Central.'],
                ['n' => 36, 'q' => 'Protectores para los competidores:', 'options' => ['Obligatorios para Cinturones Negros.', 'Opcionales para Cinturón de Color.', 'Obligatorios para todos los Cinturones.', 'A criterio del Juez Central.'], 'correct' => 2, 'why' => 'El equipo de seguridad es obligatorio para todos en sparring y combat weapons.'],
                ['n' => 37, 'q' => 'Antes de comenzar la categoría de sparring, el Central debe:', 'options' => ['Verificar que todos tengan protectores.', 'Explicar las reglas generales de sparring.', 'Explicar qué esperan los jueces.', 'Todas.'], 'correct' => 3, 'why' => 'Verifica equipo, explica reglas y expectativas antes de empezar.'],
                ['n' => 38, 'q' => 'Un Juez puede cambiar el color de la bandera en el caso de:', 'options' => ['Que los otros jueces hayan indicado al otro competidor.', 'Apunta al rojo con la bandera blanca.', 'Cuando el público pifia.', 'Ninguna.'], 'correct' => 1, 'why' => 'Si apunta al competidor correcto pero levanta el color equivocado, puede corregir el color.'],
                ['n' => 39, 'q' => 'Si un Juez escucha un punto pero no lo ve, ¿qué marca?', 'options' => ['1 punto.', 'No punto.', 'Advertencia.', 'No visto.'], 'correct' => 3, 'why' => "Debe ver, no solo oír. Si no lo vio, marca 'No visto' y sale de la votación."],
                ['n' => 40, 'q' => 'Si un competidor muestra actitudes poco deportivas, ¿qué acción se toma?', 'options' => ['Sancionarlo con una advertencia.', 'Ignorarlo.', 'Hacer flexiones.', 'Descalificarlo sin advertencia previa.'], 'correct' => 0, 'why' => 'Advertencia por conducta antideportiva (a criterio del Central; puede escalar a punto o descalificación).'],
                ['n' => 41, 'q' => 'El Central llama a punto y se marcan: 2 rojos, 3 rojos y 1 rojo. ¿Qué puntuación se entrega?', 'options' => ['Cero puntos.', '3 puntos rojos.', '1 punto rojo.', '2 puntos rojos.'], 'correct' => 3, 'why' => 'Mayoría a rojo. La puntuación común más alta apoyada por la mayoría (2 de 3 dieron 2+) es 2.'],
            ],
            3 => [
                ['n' => 42, 'q' => 'Un Juez Nivel 3 está certificado para cubrir ¿cuál posición?', 'options' => ['Central en Cinturones de Color.', 'Esquina en Cinturones Negros.', 'Central en Cinturones Negros.', 'Todas.'], 'correct' => 3, 'why' => 'Nivel 3: esquina/central en color, esquina en negro hasta su rango y central en negro hasta un rango menor al suyo.'],
                ['n' => 43, 'q' => 'Las responsabilidades del Juez Central son:', 'options' => ['Velar por las reglas.', 'Controlar su ring.', 'Controlar el traspaso de resultados a la planilla.', 'Todas las anteriores.'], 'correct' => 3, 'why' => 'Cumple las tres funciones.'],
                ['n' => 44, 'q' => 'Una vez recibida la categoría, el Central debe:', 'options' => ['Revisar mismo grado y rango de edad.', 'Dejar la categoría calentando.', 'Supervisar planillas necesarias.', 'A y C.'], 'correct' => 3, 'why' => 'Confirma grado/edad y supervisa planillas; no calienta competidores.'],
                ['n' => 45, 'q' => '¿Cuál es el orden de las competencias?', 'options' => ['Fórmula, Armas, Sparring, CW, Creative, Xtreme.', 'Armas, Fórmula, Sparring.', 'Xtreme, Fórmula, Sparring, Armas.', 'Fórmula, Armas, CW, Sparring, Creative, Xtreme.'], 'correct' => 3, 'why' => 'Forma → armas → combat weapons → sparring → creative → xtreme.'],
                ['n' => 46, 'q' => 'Mientras la planilla se completa, ¿qué debe hacer el Central?', 'options' => ['Esperar a que se termine la planilla.', 'Presentar a los jueces y comenzar la categoría.', 'Explicar las reglas generales a los competidores.', 'B y C.'], 'correct' => 3, 'why' => 'Aprovecha para presentar jueces, explicar reglas y comenzar.'],
                ['n' => 47, 'q' => 'Si un menor se lesiona (lesión media), para continuar el Central debe:', 'options' => ['Esperar autorización de los paramédicos.', 'Esperar autorización del instructor (si no están los padres).', 'Ambas en el mismo orden.', 'Ambas en cualquier orden.'], 'correct' => 2, 'why' => 'Primero decide el equipo médico; luego el instructor/padres si están presentes (en ese orden).'],
                ['n' => 48, 'q' => 'Si un Juez Nivel 3 no está de acuerdo con una regla, puede:', 'options' => ['Alterarla.', 'Modificarla consultando a los de esquina.', 'Pedir autorización al Jefe de Torneos para modificarla.', 'Ninguna.'], 'correct' => 3, 'why' => 'Ningún juez puede alterar o modificar las reglas ATA; solo consultar al RTTL por interpretación.'],
                ['n' => 49, 'q' => 'Durante el torneo, ¿qué calzado debe usar un juez?', 'options' => ['Sandalias.', 'Deportivas cualquiera.', 'Deportivas blancas.'], 'correct' => 2, 'why' => 'Descalzo o zapatillas predominantemente blancas (o negras). Las sandalias ATA no sirven para jueces.'],
                ['n' => 50, 'q' => 'El Juez Central en una competencia evaluará:', 'options' => ['Patadas y posiciones.', 'Básicos y defensas.', 'Actitud y memoria.', 'Todas.'], 'correct' => 3, 'why' => 'El Central evalúa la presentación general (todos los criterios).'],
                ['n' => 51, 'q' => 'Un Cinturón de Color, ¿cuántas veces puede repetir su fórmula?', 'options' => ['Dos veces.', 'Cuantas necesite si es tiny tiger.', 'Una vez.', 'Ninguna.'], 'correct' => 3, 'why' => 'Una sola presentación: no se repite en ninguna categoría.'],
                ['n' => 52, 'q' => '¿Cuántos puntos por salir 2do lugar en un torneo panamericano?', 'options' => ['5', '8', '10', '12'], 'correct' => 2, 'why' => 'El Panamericano es Clase AA (Nacional): 2do lugar = 10 puntos.'],
                ['n' => 53, 'q' => 'Si un competidor llega tarde a su pista, lo correcto es:', 'options' => ['Entra a fórmula mientras no se presente el último.', 'Entra a CW mientras no corra el 2do encuentro de 1ra ronda.', 'Entra a fórmula mientras no se llame al 1er competidor de armas.', 'Si quedan 3 por presentar en fórmula, se acepta y presenta de último.'], 'correct' => 3, 'why' => 'Si la modalidad no se cerró, se acepta y compite de último sin tiempo extra.'],
                ['n' => 54, 'q' => 'Si se salta un paso en su fórmula, los Jueces de Esquina deben:', 'options' => ['Ignorar bajando el puntaje máximo.', 'Advertir y repetir.', 'Bajar el puntaje.', 'No hacer nada.'], 'correct' => 3, 'why' => 'Los de esquina solo juzgan técnicas mostradas; el descuento por omisión es del Central.'],
                ['n' => 55, 'q' => 'Una fórmula incompleta se define como:', 'options' => ['Fórmula sin gritos.', 'Olvidar o saltarse 2 movimientos.', 'No terminar donde comenzó.', 'Omitir al menos 4 movimientos consecutivos.'], 'correct' => 3, 'why' => 'Omitir 4+ movimientos consecutivos o detenerse y no continuar.'],
                ['n' => 56, 'q' => 'Si un competidor sale del perímetro (fórmula):', 'options' => ['El Central descuenta.', 'Los de esquina descuentan.', 'No se descuentan puntos.', 'Dos salidas y el Central descuenta.'], 'correct' => 2, 'why' => 'No hay restricción de límites en fórmula.'],
                ['n' => 57, 'q' => 'Terminada la competencia de fórmulas, el Central debe:', 'options' => ['Confirmar que todos se presentaron.', 'Verificar la puntuación.', 'Verificar empates.', 'Todas.'], 'correct' => 3, 'why' => 'Confirma presentaciones, verifica puntajes y empates.'],
                ['n' => 58, 'q' => 'Si un competidor ajusta su posición para evitar un obstáculo, los jueces deben:', 'options' => ['Ignorar el hecho.', 'Reducir el puntaje.', 'Dejar que repita.', 'Incrementar el puntaje por manejarlo.'], 'correct' => 0, 'why' => 'No se penaliza ajustar la posición para evitar obstáculos o límites.'],
                ['n' => 59, 'q' => 'Si hay 2 o 3 competidores en una categoría, el procedimiento es:', 'options' => ['Sacar uno a la vez y dar puntaje inmediato.', 'Sacar a los competidores y luego indicar al mejor entre ellos.', 'Sacar uno por uno y luego dar el puntaje.', 'Ninguna.'], 'correct' => 1, 'why' => 'Con 2-3, no se dan notas: cada juez apunta su elección (1°=9, 2°=8, 3°=7).'],
                ['n' => 60, 'q' => 'Cuando hay empate en fórmula, el Central instruye a los de esquina a:', 'options' => ['Calificar según sus puestos.', 'Calificar la fórmula completa.', 'Cambiar el orden de evaluación (A por B).', 'Puntuar nuevamente.'], 'correct' => 1, 'why' => 'En desempate los tres jueces evalúan la fórmula completa (no su asignación) y apuntan al mejor.'],
                ['n' => 61, 'q' => 'Una vez determinada la categoría de armas, el Central debe:', 'options' => ['Preguntar quiénes participarán en armas.', 'Verificar que las armas sean las oficiales.', 'Comenzar la categoría inmediatamente.', 'Todas las anteriores.'], 'correct' => 1, 'flag' => true, 'why' => "La inspección de armas (verificar que sean oficiales) es clave y va antes de comenzar. 'Comenzar inmediatamente' contradice la inspección."],
                ['n' => 62, 'q' => '¿Cuál de las siguientes acciones está permitida?', 'options' => ['Enrollar mangas a la altura del codo.', 'El cinturón puede guardarse siguiendo su línea.', 'Enrollar mangas a la altura del antebrazo.', 'Ninguna.'], 'correct' => 1, 'flag' => true, 'why' => 'El cinturón puede acomodarse siguiendo su línea.'],
                ['n' => 63, 'q' => 'Si un competidor en Xtreme Formula se pasa del tiempo:', 'options' => ['Se rebaja el puntaje.', 'El central otorga 0 y los de esquina puntúan normal.', 'No ajustar por tiempo sino por movimientos.', 'No recibe puntaje por pasarse.'], 'correct' => 1, 'why' => 'Hoy: el Central da 0 y los de esquina puntúan normal (sin descalificación).'],
                ['n' => 64, 'q' => 'En Tigers en Chile, indique la alternativa INCORRECTA:', 'options' => ['En fórmula muestran pulgares arriba y el central felicita.', 'En armas puede hacer fórmula libre de 30 seg.', 'En sparring se marca alternadamente rojo/blanco para empatar y ambos ganan.', 'En CW arbitran normal pero el marcador queda en cero y ambos ganan.', 'Se le da medalla por fórmula-Sparring y otra por cada extra.'], 'correct' => 2, 'flag' => true, 'why' => 'Falsa: el sparring de Tigers no se puntúa. Detalles de Chile.'],
                ['n' => 65, 'q' => 'La principal consideración al evaluar armas debe ser:', 'options' => ['Presentación del material y manejo por ambos lados.', 'Calidad de las patadas.', 'Tiempo y fluidez del arma.', 'A y C.'], 'correct' => 3, 'why' => 'Presentación/manejo + tiempo-fluidez; el control del arma es el criterio rector.'],
                ['n' => 66, 'q' => 'Si el arma se cae:', 'options' => ['El Central baja en negros e ignora en color.', 'Se detiene y se comienza de nuevo.', 'Todos los jueces bajan 1 pt por cada caída.', 'Se evalúa con 9.1 en negros.'], 'correct' => 2, 'why' => 'Todos los jueces descuentan 1 punto por cada caída.'],
                ['n' => 67, 'q' => 'El rol principal del Juez Central es:', 'options' => ['Determinar un ganador.', 'Cuidar la integridad de todos.', 'Determinar puntos claros.', 'Dejar contento al público.'], 'correct' => 1, 'why' => 'La seguridad de los competidores es la prioridad.'],
                ['n' => 68, 'q' => 'Protectores para los competidores:', 'options' => ['Obligatorios para negros.', 'Opcionales para color.', 'Obligatorios para todos los cinturones.', 'A criterio del Central.'], 'correct' => 2, 'why' => 'Obligatorios para todos en sparring y combat weapons.'],
                ['n' => 69, 'q' => 'Antes de comenzar el sparring, el Central debe:', 'options' => ['Verificar protectores.', 'Explicar reglas de sparring.', 'Explicar qué esperan los jueces.', 'Todas.'], 'correct' => 3, 'why' => 'Verifica equipo y explica reglas y expectativas.'],
                ['n' => 70, 'q' => 'Un Juez puede cambiar el color de la bandera en el caso de:', 'options' => ['Que los otros apunten al otro competidor.', 'Apunta al rojo con la bandera blanca.', 'Cuando el público pifia.', 'Ninguna.'], 'correct' => 1, 'why' => 'Si apunta al competidor correcto con el color equivocado, puede corregir el color.'],
                ['n' => 71, 'q' => 'Si el Central no está de acuerdo con una llamada a punto de un Esquina:', 'options' => ['Puede obviar el llamado y continuar.', 'Detener el tiempo y reunir a los jueces para explicar que no fue claro.', 'Llamar a punto pero hacer presente su desacuerdo.', 'Ninguna.'], 'correct' => 1, 'why' => 'Se detiene el tiempo y se hace una discusión informativa; decide la mayoría (el Central no anula).'],
                ['n' => 72, 'q' => 'Si un Juez escucha un punto pero no lo ve, ¿qué marca?', 'options' => ['1 punto.', 'No punto.', 'Advertencia.', 'No visto.'], 'correct' => 3, 'why' => "Marca 'No visto' y queda fuera de esa votación."],
                ['n' => 73, 'q' => 'Si un competidor muestra actitudes poco deportivas, ¿qué acción se toma?', 'options' => ['Sancionarlo con advertencia.', 'Ignorarlo.', 'Hacer flexiones.', 'Descalificarlo sin advertencia.'], 'correct' => 0, 'why' => 'Advertencia por conducta antideportiva, a criterio del Central.'],
                ['n' => 74, 'q' => 'El Central llama a punto y se marcan: 2 rojos, 3 rojos y 1 rojo. ¿Qué se entrega?', 'options' => ['Cero puntos.', '3 puntos rojos.', '1 punto rojo.', '2 puntos rojos.'], 'correct' => 3, 'why' => 'Mayoría a rojo; la nota común más alta con mayoría (2 de 3 dieron 2+) es 2.'],
                ['n' => 75, 'q' => 'Al realizar el sorteo de sparring, el Juez Central debe:', 'options' => ['Evitar compañeros de escuela en el primer y segundo sorteo.', 'Encargar el sorteo a un Esquina.', 'Pasar a 2da vuelta a campeones mundial/panamericano/nacional si hay libres.', 'Ninguna.'], 'correct' => 2, 'flag' => true, 'why' => "Los libres (byes) se priorizan a campeones (mundial → distrito/panam → estatal). La regla de 'misma escuela' aplica a la primera ronda."],
                ['n' => 76, 'q' => 'El coaching (entrenador) se permite en una categoría solo en:', 'options' => ['Tiny tigers.', 'Taekwondo for Kids.', 'Campeones Nacionales.', 'Team Sparring / CW.'], 'correct' => 3, 'why' => 'El coaching solo se permite en sparring/combat weapons por equipos.'],
                ['n' => 77, 'q' => 'Indique la alternativa que está correcta:', 'options' => ['Si son 3 competidores, salen los tres a presentar y luego se colocan las notas.', 'En Creative se permite lanzar el arma.', 'En Xtreme se descuenta 1 pt si pasa de 2 min.', 'En armas se descuenta 1 pt si rompe y consigue otra en 30 seg.', 'Con 3 en fórmula, el 2do siempre obtiene 8-8-8.'], 'correct' => 0, 'flag' => true, 'why' => 'Correcta la (a). La (e) puede ser válida en el sistema antiguo.'],
            ],
            4 => [
                ['n' => 78, 'q' => '¿Cuál es el objetivo de la evaluación en la competencia de fórmulas?', 'options' => ['Evaluar la técnica universal considerando diferencias por escuela/región.', 'Evaluar habilidad en la ejecución de técnicas.', 'Evaluar coordinación, concentración y fuerza.', 'B y C.', 'A, B y C.'], 'correct' => 4, 'why' => 'El objetivo abarca los tres: técnica universal, habilidad de ejecución y atributos (coordinación, concentración, fuerza).'],
                ['n' => 79, 'q' => 'Como Juez A, ¿qué criterio es el más importante en su evaluación?', 'options' => ['Altura de las patadas.', 'Técnica de sus patadas.', 'Estabilidad.', 'Fuerza.'], 'correct' => 1, 'flag' => true, 'why' => 'El Juez A evalúa posturas y patadas; la calidad de la técnica es lo central. La altura debe calzar con el cuerpo, no es el criterio rector.'],
                ['n' => 80, 'q' => "'El juez de esquina evalúa solo lo presentado y no juzga pasos omitidos.'", 'options' => ['Verdadero', 'Falso'], 'correct' => 0, 'why' => 'Correcto: los jueces de esquina solo puntúan las técnicas mostradas; las omisiones las evalúa el Central.'],
                ['n' => 81, 'q' => "'Un Juez B descontará puntos si el competidor hizo golpe de nudillo en vez de canto.'", 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: una técnica incorrecta la evalúa el Central, no el juez de esquina.'],
                ['n' => 82, 'q' => 'Un Juez A descontará puntos por patear con la pierna equivocada.', 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: el de esquina solo juzga la técnica mostrada; ese tipo de error lo maneja el Central.'],
                ['n' => 83, 'q' => "'Si el Central le pide subir o bajar una nota, debe hacerlo porque tiene más experiencia.'", 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: cada juez puntúa de forma independiente; el Central no puede cambiar la nota de un juez de esquina.'],
                ['n' => 84, 'q' => 'Indique qué evaluación NO está correcta:', 'options' => ['9.9 el mejor del grupo.', '9.8–9.6 mejor que el promedio.', '9.5 el promedio.', '9.4–9.1 debajo del promedio.', '9.0 fórmula incompleta, la colocan los tres jueces.'], 'correct' => 4, 'why' => 'Falsa: el 9.0 por incompleta lo coloca SOLO el Juez Central.'],
                ['n' => 85, 'q' => 'Fórmula incompleta — puntaje de los Jueces de esquina:', 'options' => ['9.0', '9.5', '9.0 ó 9.1', '9.1 a 9.9', '9.0 a 9.5'], 'correct' => 3, 'why' => 'Los de esquina puntúan normal (9.1–9.9); solo el Central pone 0/9.0.'],
                ['n' => 86, 'q' => '¿Cuántas oportunidades para hacer la fórmula hay?', 'options' => ['1 vez para todos los grados.', '2 para Negros con autorización.', '1 leadership y 1 Negro.', '1 Negro y 2 Color.', '1 Negro (excepto infantil) y 2 Color.'], 'correct' => 0, 'why' => 'Una sola oportunidad para todos; no se repite en ninguna categoría.'],
                ['n' => 87, 'q' => 'En sparring y combat weapon, indique la alternativa correcta:', 'options' => ['El perímetro negro/rojo no tiene relevancia para los puntos.', 'Los puntos en la cabeza con salto valen 2 puntos.', 'Si se llama TIEMPO antes de contacto en la pechera, el punto NO es válido.', 'Si se llama TIEMPO antes de contacto en el cabezal, el punto es válido.'], 'correct' => 3, 'flag' => true, 'why' => 'No se exige contacto para puntuar si la técnica llegó controlada al blanco legal. (Cabeza con salto valen 3, no 2.)'],
                ['n' => 88, 'q' => "¿Qué se entiende por 'Victoria Súbita'?", 'options' => ['Ganar dentro del tiempo reglamentario.', 'El competidor se lesiona y no continúa.', 'Ganar en un combate extra de 1 minuto.', 'Ganar después de un empate, en un tiempo extra.', 'B y C.'], 'correct' => 3, 'why' => 'Tras un empate, en tiempo extra el primer punto gana (sin límite de tiempo).'],
                ['n' => 89, 'q' => 'Durante un sparring, ¿en qué momento los Jueces de esquina dan sus puntajes?', 'options' => ['En cualquier momento.', 'Cuando el combate está detenido y el Central lo pide.', 'Cuando ven puntos y detienen el combate sin esperar al Central.', 'Cuando ven mala intención.', 'En cualquier momento tras llamar a punto.'], 'correct' => 1, 'why' => 'Votan simultáneamente cuando el Central detiene el combate y pide la verificación.'],
                ['n' => 90, 'q' => '¿Cuáles son las áreas de evaluación de los Jueces de esquina en fórmula?', 'options' => ['Juez A: Piernas / patadas y posiciones.', 'Juez B: Piernas / patadas y posiciones.', 'Juez A: Memoria y fluidez.', 'Juez A y B evalúan como Central.', 'Juez A: Brazos / básicos.'], 'correct' => 0, 'why' => 'Juez A = posturas y patadas (piernas); Juez B = técnicas de mano y bloqueos (brazos).'],
                ['n' => 91, 'q' => 'Si el competidor está solo en una modalidad, ¿qué puntaje le pone el Juez de esquina?', 'options' => ['9.5 por no tener media comparativa.', '9.9 como mejor de su grupo.', 'No recibe puntaje; se apunta 1er lugar.', '9.1 por ser el único.', 'La nota que el juez estime conveniente.'], 'correct' => 1, 'why' => 'El único competidor recibe 9,9,9 por ser el mejor de su grupo ese día (salvo deducción automática, como incompleta).'],
                ['n' => 92, 'q' => 'Sobre la gesticulación de Punto / No punto / No visto / Advertencia, ¿cuál alternativa es correcta? (a) Punto: apunto con el banderín del color del competidor y con la otra mano la cantidad de puntos; No punto: cruzo las manos empuñadas en X en zona baja. (b) Punto: apunto el banderín hacia arriba y con la otra mano la cantidad; No punto: manos en X abajo. (c) No visto: cruzo las manos abiertas a la altura de la cara. (d) No visto: el juez se da media vuelta. (e) Advertencia: círculo con el banderín al suelo; Penalidad: banderín al suelo sin círculo. (f) Penalidad: círculo al suelo; Advertencia: banderín al suelo sin círculo.', 'options' => ['A, D y E son correctas.', 'B, C y F son correctas.', 'A, C y E son correctas.'], 'correct' => 2, 'why' => 'Son correctas la a (punto: banderín al competidor + dedos; no punto: brazos en X abajo), la c (no visto: manos a la altura de la cara) y la e (advertencia: banderín en círculo al suelo). La b es falsa (el banderín apunta al competidor); la d es falsa (no se da media vuelta); la f invierte advertencia y penalidad.'],
                ['n' => 93, 'q' => 'Indique la alternativa CORRECTA en sparring:', 'options' => ['Permitido puño a la cara si no hay mala intención.', 'Solo patadas sin giro a zona alta; a zona media, con y sin salto.', 'Técnica de puños a zona media.', 'Técnicas en zona baja permitidas 1 vez.'], 'correct' => 2, 'why' => 'Los puños solo son legales al torso frontal (zona media).'],
                ['n' => 94, 'q' => '¿Cuál es la puntuación por zona en sparring?', 'options' => ['1 pt: puño a la cara, puño medio, patada sin salto a la pechera.', '2 pts: puño con salto, patada con salto a pechera, patada a cabeza sin salto.', '1 pt: patada con salto a la pechera, puño medio.', '3 pts: patada con salto y giro a pechera.', '2 pts: patada con salto a la pechera, patada a la cabeza sin salto.', '1 pt: puño a la pechera, patada con salto a la pechera.'], 'correct' => 4, 'why' => 'Patada con salto al cuerpo = 2 y patada a la cabeza sin salto = 2. (Puño a la cara es ilegal.)'],
                ['n' => 95, 'q' => '¿En qué situación se permite una técnica de puño a la cara?', 'options' => ['Cuando tiene protector bucal.', 'Cuando no hay mala intención.', 'Los puños están permitidos en zona alta.', 'En ningún caso.'], 'correct' => 3, 'why' => 'Ninguna técnica de mano a la cabeza/cara es legal.'],
                ['n' => 96, 'q' => 'Áreas o técnicas ILEGALES para punto en sparring:', 'options' => ['Zona baja, puño a la cara, patada en la cabeza.', 'Puño medio, puño a la cara, patada en la espalda.', 'Patada a la cabeza, patada con giro a pechera, patada con salto a la cabeza.', 'Golpes en la espalda, puño a la cara, cualquier técnica zona baja, cuello.'], 'correct' => 3, 'why' => 'Ilegales: espalda, mano a la cara, todo bajo el cinturón y el cuello. (Patada a la cabeza SÍ es legal.)'],
                ['n' => 97, 'q' => 'Consideraciones sobre el llamado de advertencias:', 'options' => ['El tiempo sigue corriendo; advertencia y luego punto; puede recibir ambos.', 'El tiempo se detiene; advertencia y luego punto; puede recibir ambos.', 'El tiempo se detiene; punto y luego advertencia; no puede recibir ambos.', 'Si recibe advertencia puede recibir un punto a favor.', 'La 2da advertencia sin contacto descuenta 1 pt a su propio marcador.', 'La 1ra advertencia sin contacto da +1 al oponente.', 'El tiempo se detiene; advertencia y luego punto; NO puede recibir advertencia y punto a la vez.'], 'correct' => 6, 'why' => 'Se detiene el tiempo, se resuelve primero la advertencia y luego el punto; nunca advertencia y punto por la misma acción.'],
                ['n' => 98, 'q' => 'Sobre el perímetro durante el sparring:', 'options' => ['Por cada salida, una penalidad.', 'Correr fuera para evitar el combate está permitido.', 'El de afuera no puede hacer puntos ni recibirlos.', 'El de adentro marca al de afuera y está obligado a dejarlo entrar.', 'El de adentro puede golpear con salto si el punto ocurre antes de tocar el suelo fuera.'], 'correct' => 4, 'why' => 'Vale el punto con salto iniciado dentro si ocurre en el aire, antes de tocar fuera. (El de afuera SÍ puede recibir puntos; no hay obligación de dejarlo entrar; 2 salidas sin penalidad.)'],
                ['n' => 99, 'q' => 'Puntuación por zona en Combat Weapons:', 'options' => ['1 pt zonas legales: pierna, brazo, torso, espalda, estocada a la cara.', '1 pt: mano que sostiene el arma y estocada a la pierna delantera.', '2 pts: estocada pierna delantera, antebrazo y mano que sostiene arma, cabeza.', '3 pts: zona alta.', '3 pts: zona media con salto.', '2 pts: estocada a la cara y pierna delantera.', '1 pt: estocada a la cara.'], 'correct' => 2, 'why' => 'Valen 2: cabeza, brazo armado bajo el codo y estocada al muslo (pierna) delantero. Cuerpo = 1, +1 con salto, −1 por caída del arma.'],
                ['n' => 100, 'q' => '¿Qué zonas/técnicas son ILEGALES en Combat Weapons?', 'options' => ['Piernas, espalda, cabeza.', 'Estocada zona media, golpe al cuello.', 'Estocada pierna delantera, golpe a cabeza con salto, golpe en manos.', 'Estocada a la cara/ojos, zona genital y cuello.', 'Golpes con salto.', 'Solo golpes en la espalda.'], 'correct' => 3, 'why' => 'Ilegales: ingle, estocada a los ojos/cara y el cuello no cubierto por el casco.'],
                ['n' => 101, 'q' => '¿En qué casos se descuenta en una presentación de armas tradicional?', 'options' => ['Por caída del arma previo a la presentación.', 'Por dejar caer, recoger mal y olvidar 1–2 pasos.', 'Por romperse el arma.', 'Por incompleta, todos los jueces 9.0.', 'Incompleta: solo el Central 9.0; caída −1 de todos; mala recogida −1 de todos.', 'Solo por caída durante la presentación.'], 'correct' => 4, 'why' => 'Incompleta = 0 solo del Central. Caída = −1 de cada juez. Recoger mal (sin rodilla al suelo y ambas manos) = −1 de cada juez.'],
                ['n' => 102, 'q' => '¿Cuál corresponde a una advertencia de CONTACTO?', 'options' => ['Coaching, salidas reiteradas, patada zona baja.', 'Puño a la cara, patada zona baja, patada en el brazo.', 'Salidas reiteradas.', 'Dejarse caer.', 'Punto con el pie de apoyo fuera del perímetro.', 'Salto y giro a zona alta, golpe genital, golpe en espalda, puño a la cara.', 'Técnicas en la espalda, mano a la cara, golpe bajo el cinturón.'], 'correct' => 6, 'why' => 'Contacto = golpe ilegal que impacta: espalda, mano a la cara, bajo el cinturón. (Coaching, salidas, dejarse caer son SIN contacto.)'],
                ['n' => 103, 'q' => '¿Cuál corresponde a una advertencia de NO contacto?', 'options' => ['Coaching y salidas reiteradas.', 'Puño a la cara, dejarse caer, hacer tiempo, recibir instrucciones.', 'Salidas reiteradas, dejarse caer, hacer tiempo, recibir instrucciones.', 'Dejarse caer.', 'Punto con pie de apoyo fuera.', 'Hacer tiempo.', 'Regresar lento al llamado.'], 'correct' => 2, 'why' => 'Sin contacto: salidas reiteradas, dejarse caer, hacer tiempo y recibir coaching. (Puño a la cara es de contacto.)'],
                ['n' => 104, 'q' => 'Golpe de puño a la cara es penalidad y otorga un punto al oponente.', 'options' => ['Verdadero', 'Falso'], 'correct' => 0, 'why' => 'Verdadero: es contacto ilegal → +1 al rival (1ra infracción).'],
                ['n' => 105, 'q' => 'Golpe en zona baja se indica como advertencia sin penalización de puntos.', 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: un golpe (contacto) en zona ilegal da +1 al oponente.'],
                ['n' => 106, 'q' => 'En sparring, un golpe en la espalda o el cuello no están permitidos.', 'options' => ['Verdadero', 'Falso'], 'correct' => 0, 'why' => 'Verdadero: la espalda y el cuello son zonas ilegales.'],
                ['n' => 107, 'q' => 'Exceso de contacto descalifica inmediatamente a un competidor.', 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: queda a criterio del Central (puede ser punto o descalificación); solo la malicia descalifica de inmediato.'],
                ['n' => 108, 'q' => "Si el Central indica advertencia y los de esquina cobran 'no advertencia', prima el Central.", 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: decide la mayoría; el voto del Central vale igual que el de los demás.'],
                ['n' => 109, 'q' => 'Recibir coaching/indicaciones se penaliza con 1 pt en el primer llamado.', 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: la 1ra advertencia sin contacto es solo verbal; desde la 2da da +1 al rival.'],
                ['n' => 110, 'q' => 'Golpe de puño a la cara está permitido 1 vez sin ser penalizado.', 'options' => ['Verdadero', 'Falso'], 'correct' => 1, 'why' => 'Falso: nunca se permite; es advertencia de contacto (+1 al rival).'],
                ['n' => 111, 'q' => 'Indique la alternativa que está correcta:', 'options' => ['Juez B con 3 competidores: salen los tres y apunta con criterio como Juez Central por el 1er lugar.', 'En Creative Weapons se permite lanzar el arma.', 'En Xtreme se descuenta 1 pt de los tres jueces si pasa de 2 min.', 'En armas se descuenta 1 pt si rompe y consigue otra en 30 seg.', 'Con 3 competidores en fórmula tradicional, el 2do lugar obtiene 8-8-8.'], 'correct' => 4, 'why' => "Correcta la (e): con 2–3 competidores se apunta 1°=9, 2°=8, 3°=7 por juez. La (a) es falsa porque el Juez B apunta según su rol, no 'como Central'."],
                ['n' => 112, 'q' => "En 'fórmula de armas' (armas tradicionales), indique la alternativa correcta:", 'options' => ['Juez A: posiciones y patadas; Juez B: movimientos y control del arma.', 'Juez A: movimientos y control del arma; Juez B: posiciones y patadas.', 'Juez A y B evalúan la actuación completa (general), igual que el Central.'], 'correct' => 2, 'flag' => true, 'why' => 'En armas tradicionales (2025-26) todos los jueces evalúan la ejecución completa; solo el Central considera si está incompleta.'],
            ],
        ];
    }
}
