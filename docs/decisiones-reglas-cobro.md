# Reglas del reglamento como datos (configuración)

Para que el sistema pueda servir a otra federación, las reglas del reglamento del
alumno dejan de ser constantes/literales y pasan a ser **datos**.

## Nivel de cada parámetro (decisión)

Se adopta la sugerencia: **nivel de sede con respaldo de la federación**, porque el
cobro ya es por sede. La federación lleva el valor base (no nulo, con el valor
actual por defecto) y cada sede puede sobrescribirlo (columna nullable = "usar el
de la federación"). El resolvedor vive en `App\Models\Sede::parametroCobro()` y sus
métodos `clasesGraciaMorosidad()`, `exencionMatriculaDesdeMes()`,
`exencionMatriculaHastaMes()`, `diaVencimientoMaximo()`.

| Parámetro | Federación (default) | Sede | Lee |
| --- | --- | --- | --- |
| Clases de gracia antes del bloqueo por deuda | `clases_gracia_morosidad` = 3 | override nullable | `ServicioPagos::clasesGracia()` / `estaBloqueadoPorDeuda()` |
| Ventana de exención de matrícula (mes desde/hasta) | `exencion_matricula_desde_mes` = 10, `exencion_matricula_hasta_mes` = 1 | override nullable | `ServicioCargos::exentaDeMatricula()` |
| Día máximo de vencimiento de la mensualidad | `dia_vencimiento_maximo` = 20 | override nullable | `InscribirAlumno::diasVencimiento()` |

Los valores por defecto son exactamente los actuales, así que **el comportamiento no
cambia** hasta que una federación/sede los ajuste.

## Tipo de cargo por código estable

`ServicioCargos` ya **no** busca el tipo de cargo por el nombre literal
('Matrícula' / recurrente). `tipos_cargo` gana una columna `codigo` (marca estable:
`matricula`, `mensualidad`, …) y los servicios buscan por código, con el nombre solo
como respaldo. Así, si la federación renombra el tipo visible, el cobro no se rompe.

## Consulta abierta (pendiente de decisión de la escuela)

**Bloqueo por deuda vs. horas de formación Legacy de los ayudantes.**
El bloqueo por deuda impide marcar *Presente* (`ServicioPagos::estaBloqueadoPorDeuda`
usado en `TomarAsistencia`). Como las horas del Programa Legacy se acumulan desde la
asistencia con papel de **ayudante**, un ayudante con deuda dejaría de acumular horas
de formación mientras esté bloqueado.

Queda anotado el efecto para que la escuela decida:

- **A)** Es lo buscado: la deuda también congela la acumulación de horas Legacy.
- **B)** El bloqueo debe **eximir a los ayudantes** (que su asistencia como ayudante
  se registre aunque tengan deuda, para no frenar su formación).

No se implementa ninguna de las dos hasta tener la decisión; hoy rige **A** por ser
el comportamiento actual (el bloqueo aplica a toda marca de Presente).
