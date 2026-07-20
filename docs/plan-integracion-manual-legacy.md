# Plan de integración — Manual ATA Legacy (Facilitator Manual)

> Borrador para planificar. Fuente: `Manual_ATA_Legacy_ES.docx` (texto limpio) +
> `docs/planificador-unificado.tsx` (prototipo ya portado) + el PDF original
> (imágenes, para los class planners).

## 1. Qué es el manual

No es solo "class planners": es el manual del **Programa ATA Legacy**, un
**track de desarrollo de instructores** con 3 niveles (100 h cada uno) + su
currículo de referencia. Secciones:

1. **Planificadores de clase** (6 ciclos, uno por Habilidad para la Vida × 8 semanas).
2. **Programa Legacy Nivel 1/2/3** — administración: registro de asistencia
   (100 h), solicitud de ascenso, requisitos, hoja de trabajo del plan de
   estudios, prueba escrita (N3).
3. **Currículo de referencia**: Patadas por cinturón/grado, Formas (Songahm…)
   con pasos, Protech/armas (checklist), Cuadrantes de Enseñanza (items
   alumno/instructor), significado de colores de cinturón.
4. **Ventas & Marketing / Políticas** (negocio y administración ATA HQ).

## 1b. El set completo de manuales (Google Drive) y la dimensión "Programa"

La carpeta de Drive tiene **todos los manuales ATA**, no solo Legacy. El sistema
YA tiene el concepto de **Programa** (`App\Models\Programa`, `TipoPrograma`,
`planillas.programa_id`, `estudiantes` por programa), así que la integración es
"poblar cada programa con su contenido", no inventar el eje.

| Manual (Drive) | Programa | Qué aporta | Bucket |
|---|---|---|---|
| `ata_tigers_manual` | **Tigers** (3–6) | Sistema de grados Tiger, testing/ceremonia, **recompensas (Star Tag, buenas acciones)**, personajes, marketing | Grados + Recompensas (nuevo) + negocio |
| `ata_mak_manual` | **Karate for Kids / MAK** | Currículo For Kids | Contenido curricular |
| `ata_legacy_*` | **Legacy** | Track de instructores (N1-3) + currículo + class planners | Legacy operativo + contenido |
| `ata-max-curriculum` (+ nivel 2) | **ATA MAX (Xtreme)** | Currículo de formas/armas Xtreme (add-on) | Contenido curricular |
| `leadership-upgrade-script`, `7-step-upgrading-process` | **Leadership** | Scripts de venta/upgrade | Negocio (fuera de alcance) |

**Consecuencia de diseño:** casi todos los catálogos curriculares (formas,
patadas, armas, class planners) llevan una arista **por programa / grupo etario**.
Buena parte del currículo técnico ATA es **común** y se diferencia por edad
(Tigers vs For Kids vs Jóvenes y Adultos) — igual que hoy hacen las planillas.
Lo específico de cada programa: sistema de grados, recompensas (Tigers) y
ceremonias.

> **Nota Tigers:** el manual Tigers es sobre todo **operación y negocio** (grados,
> ceremonia, recompensas Star Tag, mascotas, marketing, "Día del Tigre"). Lo
> integrable: **grados Tiger** (→ `Grado`/`EscalaGrado`, ya existen) y un posible
> módulo de **recompensas/gamificación** (Star Tag, hojas de buenas acciones). El
> resto (marketing, lanzamientos) queda fuera.

## 2. Los tres "buckets" (así se decide qué entra y dónde)

### A. Contenido curricular — **transversal** (catálogo compartido, sin `academia_id`)
Extiende lo que ya existe. Igual para toda la federación.

| Contenido del manual | Dónde va | ¿Ya existe? |
|---|---|---|
| **Cuadrantes de Enseñanza** (Estructura/Emoción/Conocimiento/Legado, con 10 items alumno + instructor c/u) | Enriquecer el enum `Cuadrante` con un catálogo `cuadrante_items` (cuadrante, rol, orden, texto) | Enum existe; falta el detalle |
| **Patadas** por cinturón y por dan | Catálogo `patadas` ligado a `Grado` (cinturón) — o enriquecer `curriculos_nivel.patadas` | `curriculos_nivel` tiene un resumen; el manual trae el catálogo canónico |
| **Formas (Songahm…)** con pasos y significado | **Reusar el LMS Formación (Fase 2)**: cada forma = un `Contenido` dentro de un `Nivel`; o un catálogo `formas` dedicado | LMS existe; decidir reuso vs catálogo nuevo |
| **Protech / armas** (checklist niveles 1-3) | Catálogo `protech` (arma, nivel, item) | No |
| **Colores de cinturón (significado)** | Enriquecer `Grado` (Fase 1) con `significado`/`lema` | `Grado` existe |
| **Class Planners (6 ciclos × 8 sem)** | Backbone **`Ciclo`** (ver §3) que unifica `lecciones_vida` + `planificaciones_cinturon_negro` | Prototipo parcial ya portado |

### B. Programa Legacy — **operativo** (por academia, con `academia_id`)
Subsistema **nuevo**: seguir a un usuario (instructor en formación) por el track
Legacy. Paralelo al de alumnos/exámenes, pero para desarrollo de instructores.

- `programa_legacy_niveles` (catálogo: Nivel 1/2/3, horas requeridas = 100, orden).
- `inscripciones_legacy` (user_id, nivel, academia_id, estado, fechas).
- `horas_legacy` (registro de asistencia: fecha, horas, verificado_por) → suma hacia las 100 h.
- `requisitos_legacy` + checklist por inscripción (plan de estudios demostrado "CC",
  membresía ATA, Youth Protection, 100 h, etc.).
- Ascenso: transición de estado con verificación del licenciatario (≈ como
  finalizar convocatoria en Exámenes).
- Prueba escrita N3: podría reusar/estirar el módulo de exámenes o ser simple registro.

Se conecta con **roles** (instructor/profesor) y **rangos** ya existentes.

### C. **Fuera de alcance** del sistema (negocio/admin ATA HQ)
Ventas, marketing, precios, política de parches, formularios de envío a ATA HQ,
N.º de Seguro Social, verificación de antecedentes. → No como datos del sistema.
A lo sumo, enlaces/PDF de referencia. **Recomendación: dejar fuera.**

## 3. Modelo propuesto: el backbone `Ciclo`

El manual se organiza canónicamente en **6 Ciclos** (una Habilidad para la Vida
cada uno) × **8 semanas**. Hoy tengo eso suelto (`lecciones_vida` por semana y
`planificaciones_cinturon_negro` en 4 bloques) porque el prototipo era una vista
reducida. Propongo unificar:

```
Ciclo (habilidad_vida, orden 1–6, nombre)              [transversal]
 ├── SemanaCiclo (ciclo_id, rango "1&2".."7&8", orden)
 │     ├── LeccionVida  (texto + frase en 3 momentos)   ← reubica lecciones_vida
 │     └── (contenido del class planner por fila)
 └── ClassPlannerFila (ciclo_id, fila: warmup/kicks/forms/quadrants/protech/partner,
                       contenido por bloque de semanas)  ← reemplaza el planner BB
```

Esto deja el sistema fiel al manual y listo para cargar los 6 ciclos completos.
Implica un **refactor chico** de lo ya hecho en el planificador.

## 4. Restricción de porte (esfuerzo)

- **El .docx da texto limpio** para: Legacy admin, Patadas, Formas, Protech,
  Cuadrantes, colores de cinturón, ventas/políticas. → Se transcribe rápido.
- **Los class planners (grillas de los 6 ciclos) son imágenes** en el PDF (el
  traductor los marcó como "plantillas en blanco"). → Hay que **renderizar y
  transcribir** cada grilla (como hice con el prototipo). Es lo más lento.

## 5. Fases sugeridas (incrementales, commit por fase)

1. **Backbone `Ciclo`** + reubicar `lecciones_vida` y el planner BB. (refactor)
2. **Cuadrantes de Enseñanza** (items alumno/instructor) — texto limpio, alto valor pedagógico.
3. **Catálogo de Patadas** por cinturón/grado (+ enlazar a `Grado`).
4. **Formas** (decidir: LMS Formación vs catálogo) + colores de cinturón.
5. **Protech / armas**.
6. **Class Planners de los 6 ciclos** (transcripción desde imágenes) — completa el backbone.
7. **Programa Legacy operativo** (niveles, horas, requisitos, ascenso). El módulo más grande.
8. (Opc.) Ventas/Políticas como documentos de referencia.

## 6. Decisiones abiertas (para acordar antes de codear)

- **D1 — Backbone `Ciclo`**: ¿introducirlo (fiel al manual, refactor chico) o
  seguir agregando sobre las tablas actuales?
- **D2 — Formas**: ¿reusar el LMS Formación (Fase 2) o un catálogo `formas` aparte?
- **D3 — Programa Legacy operativo**: ¿lo construimos ahora (subsistema grande) o
  primero cargamos todo el **contenido curricular** (buckets A) y dejamos el
  track de instructores para después?
- **D4 — Prioridad**: ¿por dónde empezamos? (sugerido: 1 → 2 → 3)
- **D5 — Programas**: ¿cargamos el contenido de **todos** los programas (Tigers,
  For Kids/MAK, Jóvenes y Adultos, MAX, Legacy) o arrancamos por uno? El currículo
  técnico es en gran parte común y se diferencia por grupo etario; lo específico
  por programa es grados, recompensas (Tigers) y ceremonias.
- **D6 — Recompensas Tigers**: ¿incluimos el módulo de gamificación (Star Tag,
  buenas acciones) o lo dejamos para una fase posterior?

## 8. Síntesis tras leer Tigers / MAK / MAX / Legacy — arquitectura recomendada

Leídos los `.docx` traducidos, el patrón es MUY consistente entre programas. La
arquitectura que propongo (de mayor a menor prioridad):

1. **Backbone = las 6 Habilidades de Vida Songahm** (Disciplina, Convicción,
   Comunicación, Respeto, Autoestima, Honestidad). Aparecen en TODOS los manuales
   como eje: Legacy (ciclos), MAK (coleccionables + tarjetas de reporte), MAX
   ("incorpora las habilidades…"), Tigers (serie de libros). **Es exactamente el
   enum `HabilidadVida` que ya existe.** → `Ciclo` keyed por habilidad es la
   decisión correcta (D1 = sí).

2. **Sistema de grados Clásico Songahm** (10 colores × recomendado/decidido +
   danes), **compartido** por todos los programas. Ya está en `Grado`/`EscalaGrado`
   (Fase 1). Enriquecer con recomendado/decidido, franjas y significado.

3. **Biblioteca de técnicas (catálogo transversal, nuevo)** — lo más grande. Unifica
   TODO el currículo técnico disperso en los manuales:
   - Categorías: **Patadas, Formas, Manos, Tricks, Armas** (Jahng Bong, Ssahng Jeol
     Bong, Ssahng Nat, Gum Do), **Rompimientos**, Protech.
   - Etiquetas por técnica: **cinturón/dan**, **modalidad** (Tradicional / Creative /
     Xtreme / Tricking), **core vs electivo**, **programa(s)** aplicable(s).
   - Las **planillas** (ya transversales) referencian técnicas y se arman con el
     enfoque **3-2-1** (3 Core + 2 Electivo + 1 Habilidad de Vida).
   - Sustituye/ordena lo que hoy es texto libre en `curriculos_nivel.patadas/formula`.

4. **Módulo de progreso / recompensas (operativo, por alumno/academia)** — patrón
   repetido: **Franjas de conocimiento** (MAK: 3 negras + amarilla/azul/roja/verde),
   **Star Tag** (Tigers), **Coleccionables** (6, uno por habilidad). Es gamificación
   por ciclo, ligada a grado y a habilidades de vida. Un solo módulo cubre los tres.

5. **Programa Legacy (operativo, por academia)** — track de instructores N1-3 (100 h,
   requisitos, ascenso). Subsistema aparte, el más grande del bucket operativo.

6. **`Programa` (ya existe)** agrupa, por cada programa (Tigers/MAK/Jóvenes-Adultos/
   MAX/Legacy), qué técnicas, planillas, grados y recompensas aplican.

**Fuera de alcance** (confirmado): Leadership upgrade scripts, 7-step upgrading,
marketing, precios, políticas de parches, formularios ATA HQ, descuentos de
proveedores US (Fitnessfinders), avatares/mascotas como narrativa de negocio.

### Fases recomendadas (actualizadas)
1. `Ciclo` (backbone) + reubicar lecciones/planner. 2. **Biblioteca de técnicas**
(catálogo + etiquetas) — cargar Patadas/Formas/Armas/Tricks desde los `.docx`.
3. Enriquecer `Grado` (recomendado/decidido, significado). 4. Cuadrantes de
Enseñanza (items). 5. Class planners de los 6 ciclos (imágenes). 6. Módulo de
recompensas/progreso. 7. Programa Legacy operativo.

## 7. Fuentes

Todos los manuales están en Google Drive (carpeta compartida por el dueño). Para
portar contenido conviene trabajar desde los `.docx` traducidos (texto limpio);
las grillas de class planner solo están como imagen en los PDF.

