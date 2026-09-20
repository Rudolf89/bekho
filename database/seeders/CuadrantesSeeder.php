<?php

namespace Database\Seeders;

use App\Enums\Cuadrante;
use App\Enums\RolCuadrante;
use App\Models\CuadranteItem;
use Illuminate\Database\Seeder;

/**
 * Cuadrantes de Enseñanza (marco pedagógico ATA, Nivel 2): Estructura, Emoción,
 * Conocimiento y Legado, cada uno con responsabilidades del alumno y del
 * instructor. Portado del Manual Legacy. Catálogo compartido, idempotente.
 *
 * Estructura trae el detalle completo; el resto por ahora solo la lista (el
 * detalle largo de Emoción/Conocimiento/Legado queda pendiente en el manual).
 */
class CuadrantesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->items() as $cuadrante => $roles) {
            foreach ($roles as $rol => $lista) {
                foreach ($lista as $i => $item) {
                    [$texto, $detalle] = is_array($item) ? $item : [$item, null];
                    CuadranteItem::updateOrCreate(
                        ['cuadrante' => $cuadrante, 'rol' => $rol, 'orden' => $i + 1],
                        [
                            'texto' => $texto,
                            'detalle' => $detalle,
                            'fuente' => ManualLegacySeeder::FUENTE,
                            'verificado' => true,
                        ],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array<string, list<string|array{0: string, 1: string}>>>
     */
    private function items(): array
    {
        $a = RolCuadrante::Alumno->value;
        $i = RolCuadrante::Instructor->value;

        return [
            Cuadrante::Estructura->value => [
                $a => [
                    ['Saludar (reverencia) en la puerta y en el tatami', 'El alumno entra y sale diciendo “Hola, señor/señora — Adiós, señor/señora”. Enseña comunicación y respeto, y permite a los instructores notar su presencia.'],
                    ['Bolsos y zapatos en su lugar', 'Disciplina: la escuela es un lugar de respeto. Si no hay un lugar designado, se alinean los bolsos uno junto a otro con los zapatos dentro.'],
                    ['Retirar la tarjeta de asistencia', 'Inmediatamente después de saludar y ordenar sus cosas, retira su tarjeta del tarjetero. Facilita una llamada de “te extrañamos” cuando alguien falta.'],
                    ['Formar fila por estatura', 'Crea un ambiente ordenado: fila recta del más bajo al más alto antes de entrar a clase; el más bajo adelante para mejor visibilidad.'],
                    ['Entregar las tarjetas', 'A la orden “¡Tarjetas afuera!”, abren los pies, extienden los brazos y sostienen la tarjeta entre las yemas. El más alto las recoge y se las entrega al instructor.'],
                    ['Giro a la derecha / izquierda', 'Forma militar de dar estructura: golpean el piso dos veces diciendo “¡Sí, señor! ¡Listo!” y llevan las manos a los costados en atención.'],
                    ['Tomar y guardar el equipo por fila', 'El equipo se toma de forma ordenada, evitando correr y el desorden. Resalta la disciplina de la clase.'],
                    ['Posición de escucha', 'El alumno se arrodilla con ambas manos sobre las rodillas, o adopta una posición de bloqueo bajo.'],
                    ['Fila 1 frente a Fila 2', 'Ejercicios en pareja: se procede igual que al tomar/guardar el equipo por fila.'],
                    ['Salida por filas', 'Se despide en orden de estatura; cada uno toma su bolso y zapatos y hace la reverencia al salir.'],
                ],
                $i => [
                    ['Puntos marcados en el piso', 'Deben ser visibles y mantener una distancia segura entre sí para la práctica.'],
                    ['Blancos (targets) en la pared', 'Todos los blancos ordenados contra la pared. Evitar apilarlos por higiene.'],
                    ['Cintas precortadas, estrellas en papel, parches con nombre', 'Cintas precortadas y listas; estrellas en papel de premiación; el parche de Karate for Kids se entrega al traer un invitado; todo alumno nuevo con su nombre en un parche visible.'],
                    ['Reverencia a los padres / a las banderas', 'Momento de conectar con los padres: el instructor camina hacia ellos, da el ejemplo, los saluda y los hace sentir parte de la clase.'],
                    ['Recitar el juramento correspondiente', 'Juramento Tiger de ATA, juramento Karate Kid y el Espíritu Songahm.'],
                    ['Calendario, folletos y pases VIP', 'Cada evento listo con un folleto o invitación para repartir al final de la clase.'],
                    ['Equipo adecuado', 'El equipo de protección debe ser apropiado y revisarse durante la clase a medida que se usa.'],
                    ['Inspección de uniforme', 'En la formación: uniformes planchados, limpios y sin olor, con el cinturón bien atado.'],
                    ['Imagen impecable', 'La apariencia del instructor debe ser intachable: cabello, olor, sin piercings ni tatuajes visibles.'],
                    ['Seguir el planificador de clase', 'Cubrir todos los puntos del planificador. No desviarse del sistema es lo que hace crecer a la escuela.'],
                ],
            ],
            Cuadrante::Emocion->value => [
                $a => [
                    'Actitud “¡Sí puedo!”', 'Tono de voz', 'Postura corporal', 'Movimiento corporal / reacción inmediata',
                    'Intensidad', 'Expresiones faciales', 'Respiración correcta / KIHAP fuerte', 'Dramatismo positivo',
                    'Aliento (alentar a los demás)', 'Celebrar',
                ],
                $i => [
                    'Contacto visual', 'Voz de mando', 'Ritmo y picos de voz (pace and peak)', 'Sonrisa',
                    'Destacar (highlight)', 'Demostración espectacular', 'Contacto físico adecuado', 'Historia personal',
                    'Dramatismo e intensidad', 'Involucrar a los padres',
                ],
            ],
            Cuadrante::Conocimiento->value => [
                $a => [
                    'Base', 'Trayectoria', 'Extensión / acompañamiento', 'Posición articular', 'Equilibrio',
                    'Precisión', 'Velocidad', 'Fuerza de reacción', 'Potencia', 'Reflejo automático',
                ],
                $i => [
                    'Demostrar, explicar, practicar, confirmar resultados', 'Individual, blanco, compañero',
                    'Ubicación del instructor en el tatami', 'Aplicación práctica', 'Historia (origen de la técnica)',
                    'Elogiar, corregir, elogiar', 'Verbalizar las expectativas', 'Asistentes activos (apoyo directo)',
                    'Conciencia del entorno', 'Imagen en espejo',
                ],
            ],
            Cuadrante::Legado->value => [
                $a => [
                    'Disciplina: obedecer lo que es correcto',
                    'Convicción: “Sí, yo puedo”',
                    'Comunicación: el vínculo entre el mundo y yo',
                    'Respeto: no es lo que sé, es lo que hago',
                    'Autoestima: la alegría de ser yo mismo',
                    'Honestidad: el primer paso hacia una vida plena',
                ],
            ],
        ];
    }
}
