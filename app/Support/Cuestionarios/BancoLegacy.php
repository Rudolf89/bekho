<?php

namespace App\Support\Cuestionarios;

/**
 * Banco de preguntas de la prueba escrita del Programa ATA Legacy (Nivel 3),
 * fundamentado en el Manual del Facilitador ATA Legacy (v4). Cubre la estructura
 * del programa, los requisitos por nivel, los Cuadrantes de Enseñanza, las seis
 * Habilidades de Vida y el currículo físico. Es material real de estudio; el
 * examinador puede editarlo o ampliarlo desde la interfaz.
 *
 * Formato por pregunta:
 *   ['q' => enunciado, 'options' => [...], 'correct' => índice correcto,
 *    'why' => explicación]
 */
class BancoLegacy
{
    /**
     * @return array{titulo: string, area: string, descripcion: string, preguntas: list<array{q: string, options: list<string>, correct: int, why: string}>}
     */
    public static function cuestionario(): array
    {
        return [
            'titulo' => 'Examen escrito · Programa Legacy Nivel 3',
            'area' => 'Formación de instructores',
            'descripcion' => 'Prueba escrita del Programa ATA Legacy: estructura del programa, requisitos por '
                .'nivel, Cuadrantes de Enseñanza, las seis Habilidades de Vida y el currículo físico. '
                .'Aprobación mínima: 80%.',
            'preguntas' => self::preguntas(),
        ];
    }

    /**
     * @return list<array{q: string, options: list<string>, correct: int, why: string}>
     */
    private static function preguntas(): array
    {
        return [
            ['q' => '¿Cuántas horas de asistencia a clase exige cada nivel del Programa Legacy?', 'options' => ['50 horas', '75 horas', '100 horas', '150 horas'], 'correct' => 2, 'why' => 'Cada uno de los tres niveles tiene su propio bloque de 100 horas de asistencia registradas y verificadas por el instructor o dueño de la escuela.'],
            ['q' => 'Los tres niveles del Programa Legacy son progresivos. ¿Cuál es la edad mínima para ascender al Nivel 3?', 'options' => ['13 años', '16 años', '18 años', '21 años'], 'correct' => 2, 'why' => 'Nivel 1 exige 13 años, Nivel 2 exige 16 años y Nivel 3 exige 18 años (y 1.er Dan cinturón negro).'],
            ['q' => '¿Qué rango mínimo se exige para el Nivel 3 (certificación de instructor)?', 'options' => ['Cinturón rojo', '1.er Dan cinturón negro', '2.º Dan cinturón negro', 'Cinturón café'], 'correct' => 1, 'why' => 'El Nivel 3 requiere ser 1.er Dan cinturón negro y tener 18 años.'],
            ['q' => '¿Cuál certificación se exige SOLO en el Nivel 3?', 'options' => ['Membresía ATA vigente', 'Programa de Protección de Menores', 'Certificación vigente de RCP (CPR)', 'Verificación de antecedentes'], 'correct' => 2, 'why' => 'La certificación de RCP (CPR) se exige únicamente para el Nivel 3; los otros requisitos aplican a todos los niveles.'],
            ['q' => '¿Cuáles son los cuatro Cuadrantes de Enseñanza?', 'options' => ['Cuerpo, Mente, Corazón y Espíritu', 'Estructura, Emoción, Conocimiento y Legado', 'Warm-Up, Patadas, Formas y Sparring', 'Disciplina, Respeto, Honestidad y Convicción'], 'correct' => 1, 'why' => 'Los Cuadrantes de Enseñanza organizan la clase en Estructura, Emoción, Conocimiento y Legado, cada uno con responsabilidades del alumno y del instructor.'],
            ['q' => '¿A qué Cuadrante de Enseñanza corresponde el desarrollo del carácter mediante las seis Habilidades de Vida?', 'options' => ['Estructura', 'Emoción', 'Conocimiento', 'Legado'], 'correct' => 3, 'why' => 'El cuadrante Legado se centra en formar las seis Habilidades de Vida: Disciplina, Convicción, Comunicación, Respeto, Autoestima y Honestidad.'],
            ['q' => 'En el cuadrante Conocimiento, ¿cuál es el proceso de cuatro pasos para enseñar una técnica?', 'options' => ['Mirar, imitar, corregir, repetir', 'Demostrar, explicar, practicar, confirmar', 'Explicar, memorizar, evaluar, premiar', 'Calentar, enseñar, combatir, cerrar'], 'correct' => 1, 'why' => 'El proceso es demostrar (de forma inspiradora), explicar (simple, con analogías), practicar (permitiendo errar) y confirmar los resultados destacando al individuo o grupo.'],
            ['q' => '¿Cuántos atributos técnicos se usan para evaluar cada forma y cada patada?', 'options' => ['5', '8', '10', '12'], 'correct' => 2, 'why' => 'Son diez atributos: Base, Trayectoria, Extensión, Posición articular, Equilibrio, Precisión, Velocidad, Fuerza de reacción, Potencia y Reflejo automático.'],
            ['q' => 'Según el manual, la Disciplina se define como:', 'options' => ['"Sí, yo puedo"', '"Obedecer lo que es correcto"', '"La alegría de ser yo mismo"', '"El vínculo entre el mundo y yo"'], 'correct' => 1, 'why' => 'Disciplina: es obedecer lo que es correcto. ("Sí, yo puedo" es Convicción; "la alegría de ser yo mismo" es Autoestima; "el vínculo entre el mundo y yo" es Comunicación.)'],
            ['q' => '¿Cuál de estas frases define la Habilidad de Vida "Respeto"?', 'options' => ['"Es el primer paso hacia una vida plena"', '"No es lo que sé, es lo que hago"', '"Obedecer lo que es correcto"', '"Sí, yo puedo"'], 'correct' => 1, 'why' => 'Respeto: no es lo que sé, es lo que hago. (La Honestidad es "el primer paso hacia una vida plena".)'],
            ['q' => '¿Cuáles son las seis Habilidades de Vida Songahm?', 'options' => ['Fuerza, Velocidad, Técnica, Foco, Equilibrio y Poder', 'Disciplina, Convicción, Comunicación, Respeto, Autoestima y Honestidad', 'Cortesía, Integridad, Perseverancia, Autocontrol, Espíritu y Humildad', 'Cuerpo, Mente, Corazón, Espíritu, Familia y Comunidad'], 'correct' => 1, 'why' => 'Las seis Habilidades de Vida son Disciplina, Convicción, Comunicación, Respeto, Autoestima y Honestidad.'],
            ['q' => '¿Cuál es el porcentaje mínimo para aprobar la prueba escrita de Nivel 3?', 'options' => ['60%', '70%', '80%', '90%'], 'correct' => 2, 'why' => 'La prueba escrita de Nivel 3 se aprueba con un 80% y otorga de 45 a 60 minutos para completarla.'],
            ['q' => 'Un alumno del Programa Legacy que aún no es instructor certificado de Nivel 3, ¿puede enseñar clases?', 'options' => ['Sí, sin restricciones', 'Sí, pero solo a cinturones de color', 'Solo bajo supervisión directa del licenciatario o de un instructor certificado de Nivel 3', 'No, nunca puede estar en la clase'], 'correct' => 2, 'why' => 'Los alumnos Legacy pueden ayudar en clases, pero no pueden enseñar sin la supervisión directa del licenciatario o de un instructor certificado de Nivel 3.'],
            ['q' => 'Respecto de las solicitudes de ascenso, el manual indica que retrodatar (backdating) una solicitud:', 'options' => ['Se permite si el instructor lo autoriza', 'Se permite hasta 30 días', 'No se permite por ningún motivo', 'Solo lo permite la Sede Central'], 'correct' => 2, 'why' => 'No se permite retrodatar por ningún motivo, ya que afecta la clasificación de puntos estatales de todos los alumnos del estado.'],
            ['q' => 'Según el currículo físico del manual, ¿qué patada corresponde al cinturón Blanco?', 'options' => ['Patada frontal', 'Patada circular', 'Patada lateral (n.º 1, 2 y 3)', 'Patada de gancho'], 'correct' => 2, 'why' => 'En el currículo Legacy/ATA el cinturón Blanco trabaja la patada lateral n.º 1, 2 y 3; la circular es de Naranja y la frontal de Amarillo.'],
            ['q' => '¿Cómo se titulan las formas Songahm 1 a 5?', 'options' => ['"Una gloria inquebrantable"', '"El pino y la roca"', '"Todo resulta perfecto y hermoso"', '"Paz mental y tranquilidad"'], 'correct' => 1, 'why' => 'Las formas Songahm 1-5 se titulan "El pino y la roca"; In Wha 1-2 son "Una gloria inquebrantable" y Choong Jung 1-2 "Todo resulta perfecto y hermoso".'],
            ['q' => 'En la lista Protech de armas, ¿qué se debe demostrar como base en todos los niveles?', 'options' => ['Solo la mano derecha, hacia adelante', 'Las líneas de golpeo 1 a 9 con ambas manos y desplazamiento adelante y atrás', 'Únicamente formas con espada (Gum Do)', 'Rutinas libres de 30 segundos'], 'correct' => 1, 'why' => 'Se deben demostrar las líneas de golpeo 1 a 9 con ambas manos y desplazándose hacia adelante y atrás, manteniendo control firme del arma.'],
            ['q' => '¿Cuál es el parche que usa el alumno en el Nivel 1 de Legacy?', 'options' => ['Rojo', 'Negro/Rojo', 'Negro/Rojo/Negro', 'Negro/Dorado'], 'correct' => 1, 'why' => 'El parche del alumno es Rojo al ingreso, Negro/Rojo en Nivel 1 y Negro/Rojo/Negro en Nivel 2; el instructor usa Negro en Nivel 3.'],
        ];
    }
}
