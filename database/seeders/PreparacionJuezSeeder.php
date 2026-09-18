<?php

namespace Database\Seeders;

use App\Models\Contenido;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Support\Tenancy\Grupo as Tenant;
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
        $grupo = Grupo::where('nombre', 'BEKHO Power Academy')->first();

        if (! $grupo) {
            $this->command?->warn('No existe el grupo BEKHO; ejecuta antes RolesPermisosSeeder.');

            return;
        }

        Tenant::set($grupo->id);

        $nivel = Nivel::updateOrCreate(
            ['grupo_id' => $grupo->id, 'nombre' => 'Preparación para examen de juez nivel 1'],
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
                    'grupo_id' => $grupo->id,
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

        // --- Práctica interactiva -----------------------------------------------
        // Los cuestionarios (autocorregidos, con puntaje) viven en el módulo de
        // Cuestionarios, no aquí: este material es la lectura de estudio.
        $items[] = [
            'titulo' => 'Práctica interactiva (autocorregida)',
            'tipo' => 'texto',
            'descripcion' => 'Dónde rendir los cuestionarios con puntaje.',
            'cuerpo' => <<<'TXT'
Cuando termines de estudiar el manual, pon a prueba lo aprendido en el módulo de Cuestionarios.

Ahí encontrarás los exámenes de práctica AUTOCORREGIDOS, con puntaje y explicación por pregunta:
• Examen de Juez ATA — Nivel 1
• Examen de Juez ATA — Nivel 2
• Examen de Juez ATA — Nivel 3
• Repaso de puntuación y combate

Aprobación mínima: 80%. Puedes repetir los intentos las veces que quieras.
TXT,
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
}
