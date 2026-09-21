# CLAUDE.md — Sistema BEKHO

Guía para trabajar en este repo. **Todo el código y el dominio están en español.**

## Qué es

Plataforma de **gestión + formación** para la **federación BEKHO** de Taekwondo ATA
(Chile). Administra cada grupo/academia (alumnos, sedes, clases, asistencia, pagos,
exámenes, planillas) y ofrece formación en línea (LMS) + el currículo ATA.

## Stack

- **Laravel 13** (PHP 8.3+), monolito, **sin API**.
- **Livewire 4 + Flux UI (versión FREE)** + **Tailwind CSS 4**. Toda la UI es Blade+Livewire.
- **PostgreSQL**. Tests en SQLite in-memory.
- **Laravel Fortify** (login, 2FA, passkeys, reset).
- **spatie/laravel-permission** (roles/permisos, **sin teams mode**).

## Jerarquía y tenancy (clave)

**BEKHO es la FEDERACIÓN (la plataforma), no un grupo.** Jerarquía real:
`BEKHO (federación) → GRUPO (p. ej. "BEKHO Power Academy") → Sede → Clase`. La
tabla `federaciones` es la entidad raíz y `grupos.federacion_id` la ancla. El
**aislamiento entre grupos es total**: `grupo_id` **es la frontera real**, no
una costura futura.

Piezas del tenant:
- `App\Support\Tenancy\Grupo` — holder estático: `set(?int $id, bool $filtraLecturas = true)`, `id()`, `hayActiva()`, `filtraLecturas()`, `olvidar()`, `esSistema()`, `comoSistema(callable)`.
- `App\Models\Concerns\PerteneceGrupo` — trait: global scope que **FALLA CERRADO**.
  (1) grupo activo con filtro → filtra por `grupo_id`; (2) grupo activo sin filtro
  (`admin-plataforma`) → ve todo; (3) **sin grupo activo ni modo sistema → NO
  devuelve nada** (`whereRaw('1 = 0')`), y en consola deja un aviso en el log (una
  vez por tabla). Autorellena `grupo_id` al crear; scope `sinGrupo()` para consultas
  puntuales. Un modelo de identidad que se resuelve antes del tenant puede
  sobrescribir `fallaCerradoSinGrupo()` devolviendo `false` (**solo `User`**, porque
  la autenticación resuelve la cuenta antes de conocer el grupo).
- **`Grupo::comoSistema(fn () => …)`** — salida explícita para el código de sistema
  (seeders, comandos, jobs) que DEBE ver todos los grupos: desactiva el aislamiento
  a propósito y restaura el estado al terminar (aun con excepción; anidable).
  `DatabaseSeeder` y los servicios de sistema (`ServicioCargos::generarMensualidades`
  /`generarMatriculasAnuales`, `ServicioPagos::registrarPago`/`verificar`/`anular`)
  corren dentro de `comoSistema`. Preferir esto a `withoutGlobalScopes()`.
- **Colas:** `Queue::before` limpia el grupo activo antes de cada job
  (`AppServiceProvider`), porque el holder es estático y en un worker de larga vida
  el estado se filtraría entre trabajos; un job que deba ver todos los grupos usa
  `comoSistema`.
- `App\Http\Middleware\EstableceGrupoActual` — fija el tenant, **antes de
  `SubstituteBindings`** (prioridad en `bootstrap/app.php`) para que el route-model
  binding de un modelo con `grupo_id` quede acotado al grupo (y no se abran por URL
  registros de otro grupo). Un rol normal queda atado a su `grupo_id`. El
  **`admin-plataforma`** elige grupo en el selector: si elige uno, la vista se
  **acota** (`filtraLecturas: true`); si elige "Todos los grupos", ve todo
  (`filtraLecturas: false`). La **`federacion`** ve todo en **solo lectura**.

**Catálogos compartidos = SIN `grupo_id`** (como `cargos_rangos`): `planificaciones_clase`,
`bloques_planificacion`, `cuadrantes_planificacion`, `ciclos`, `planner_ciclo`, `tecnicas`,
`pasos_tecnica`, `formas`, `pasos_forma`, `posiciones`, `secciones_altura`,
`habilidades_vida`, `atributos_tecnicos`, `armas`, `cuadrante_items`, `grados`,
`grado_tecnica`, `curriculos_nivel`, `lecciones_vida`, biblioteca de calentamiento,
planificador de Cinturón Negro, `cuestionarios`, `preguntas_cuestionario`,
`opciones_pregunta`, `recompensas`, `distintivos_rango`, `juramentos`, `programas`,
`etapas_programa`, `requisitos_etapa`, `contenidos`, `instrumentos_evaluacion`,
`secciones_instrumento`, `criterios_instrumento`. **Transversales (identidad/formación,
sin `grupo_id` pero operativos):** `personas`, `instructores`, `tutelas`,
`creditos_graduacion`, `inscripciones_programa` (+ `horas_programa`, `cumplimientos_requisito`,
`ascensos_programa`), `progreso_contenidos`, `evaluaciones_practicas` (+ `puntajes_criterio`).
**Datos operativos = CON `grupo_id`**:
usuarios, sedes, alumnos, clases, asistencia, pagos, exámenes, `calentamiento_clase`,
`intentos_cuestionario`, `logros`.

## Personas, matrículas e identidad (rediseño completo)

**El corte de identidad está hecho.** La identidad está separada de la operación:
`personas` (identidad única de la federación, **sin `grupo_id`**, fecha de nacimiento
obligatoria, `grado_id` = caché del último grado, `documentos_persona` para
RUT/pasaporte validado con `App\Support\Rut`); `users` tiene `persona_id` (la cuenta
es solo acceso); `tutelas` (apoderado↔alumno, cruza grupos); `instructores` (faceta
marcial transversal: rango, supervisor por persona, certificación); `personal_grupo`
(trabajo en un grupo, operativo) con `personal_grupo_rol` (roles por grupo, con
`sede_id` opcional para acotar a una sede); `matriculas` (alumno↔grupo, operativo,
**una sola activa por persona** vía índice parcial, FK compuesta `(sede_id, grupo_id)`).
**`estudiantes`, `apoderado_estudiante` y `estudiante_programa` fueron RETIRADOS**
(junto al modelo `Estudiante`, la `EstudiantePolicy` y el `MigraPersonasSeeder`): la
operación corre 100 % sobre persona/matrícula. `DemoBekhoSeeder` crea directamente la
capa de identidad (personas, matrículas, personal_grupo, instructores, personal_grupo_rol).

**Operación sobre matrícula (Fase 4):** asistencia (`asistencias.matricula_id`,
`Clase::matriculasEsperadas()`), pagos y morosidad (`ServicioPagos` sobre cargos;
hermanos vía tutelas), logros (`Matricula::visiblePara` para instructor/apoderado),
exámenes y graduaciones, **suspensiones** (congelan
la morosidad) y **traslados** entre grupos (`ServicioTraslados`: consentimiento +
deuda + ejecución). La inscripción (`InscribirAlumno`) y la gestión de alumnos crean
persona + matrícula (+ documento RUT); no queda ninguna `Estudiante`.

**Exámenes y créditos de graduación:** la convocatoria tiene `tipo` (`App\Enums\
TipoConvocatoria` instructor/federación); la **nota** solo se valida en las de
instructor (escala `config('bekho.examenes.nota')` 9.1–9.9, mínimo de aprobación 9.5).
Cualquier instructor examina (permiso desde la convocatoria); el **examinador**
(`graduaciones.examinador_persona_id`) es distinto del **instructor acreditado**
(`graduaciones.instructor_acreditado_persona_id`), que recibe el crédito. El grado de
la persona **se cachea al ENTREGAR el cinturón** (`ServicioExamenes::registrarEntrega`),
nunca al aprobar; el plazo de 30 días solo alerta. Un alumno reprobado puede **volver a
rendir en la misma convocatoria** (sin único convocatoria+matrícula). **Créditos de
graduación** (`ServicioCreditos`): cada graduación suma un crédito al instructor de
origen y a **toda su cadena de supervisión** hacia arriba (puede cruzar grupos), con
protección contra ciclos. Origen: (1) instructor de la matrícula activa → (2)
`sedes.responsable_persona_id` → (3) supervisor de su faceta de instructor → (4) nunca
a sí mismo. `creditos_graduacion` (transversal, sin `grupo_id`) guarda una fila por
graduación×persona; el **distintivo** del profesor NO se guarda: se calcula contando
créditos contra `distintivos_rango` (umbrales por confirmar → nulos, no se inventan).

**Catálogos de la federación (Fase 3/5/6):** `tramos_entrenamiento`, `grados`
(con `tramo_id`, `meses_sugeridos`, `requiere_nominacion`), `escalas_puntaje`,
`tipos_cargo`, y competencia (`grupos_edad`, `categorias_competencia`, `pruebas`,
`criterios_prueba`, `tabla_libres`). **Cobros (Fase 5):** el cobro es **POR SEDE**:
`tarifas_sede` (cada sede define matrícula/mensualidad por tramo; sin tarifa no se
genera cargo, falla explícita, sin respaldo de grupo), `becas`, `cargos` (con
`sede_id`; `ServicioCargos` cuenta el tramo familiar **por sede** — hermanos en otra
sede no suman — y congela el detalle) y
**pagos con verificación** (`EstadoPago` por_verificar/verificado/anulado, comprobante,
`pago_cargo` para abonos que cubren varios cargos; `ServicioPagos` registra/verifica/
anula y aplica montos a los cargos; el apoderado sube comprobante desde su portal).
**Reglas de pago del reglamento (configurables, nivel sede con respaldo de la
federación** — `Sede::parametroCobro()` / columnas en `federaciones` y `sedes`; los
defaults son los valores actuales, ver `docs/decisiones-reglas-cobro.md`**):** la
mensualidad vence el día fijo elegido (máximo `dia_vencimiento_maximo`, def. 20,
`InscribirAlumno::diasVencimiento`); tras el vencimiento hay **clases de gracia**
(`clases_gracia_morosidad`, def. 3; `ServicioPagos::clasesGracia`/`estaBloqueadoPorDeuda`)
y luego el alumno queda bloqueado (no se le marca Presente). La **matrícula es anual**
(`generarMatriculasAnuales`, feb–mar) con **exención** para el alumno que ingresó en la
ventana `exencion_matricula_desde_mes`–`_hasta_mes` (def. octubre–enero,
`exentaDeMatricula`). Los tipos de cargo se identifican por **código estable**
(`tipos_cargo.codigo`, p. ej. `matricula`/`mensualidad`), no por el nombre. El **plan de pago**
(`matriculas.plan_pago`, enum `App\Enums\PlanPago` mensual/semestral/anual) define la
recurrencia: el mensual se cobra mes a mes (`generarMensualidades`), el semestral/anual
se cobran **por adelantado** en un solo cargo con el **descuento de la sede**
(`sedes.descuento_semestral_pct`/`descuento_anual_pct`, 0 sin configurar) vía
`ServicioCargos::montoPlan`/`generarCargoPlan`.
**Competencia operativa (Fase 6b):** `planillas_competencia`, `jueces_planilla`,
`competidores_planilla`, `puntajes_planilla` (`ServicioPlanillaCompetencia`, permiso
`gestionar competencia`) para la certificación de planillero con competidores y jueces
ficticios. Los catálogos `grupos_edad` (10, Tigers SIN rango porque la planilla no lo
indica), `categorias_competencia` (15: 10 de color hasta Rojo-Negro y 5 de negro desde
1 BD) y `tabla_libres` (2–16 competidores) se transcriben de la **planilla de competencia
oficial BEKHO** (`fuente`/`verificado=true`, `CompetenciaSeeder`). OJO: la planilla dice
"Naranjo", "Púrpura" y "Café" donde el Manual y `grados` usan "Naranja", "Morado" y
"Marrón": es la misma escala con otro nombre, manda la planilla en esa tabla y los grados
NO se renombran. **Hojas para practicar** (`App\Livewire\Competencia\HojasPractica`,
`/practica/planillas`, permiso `ver programas` porque practican planilleros, jueces y
alumnos Legacy — los menores no tienen cuenta y su instructor se las imprime): las tres
secciones de la planilla, transcritas del xlsx oficial y generadas desde los catálogos
con el componente Blade `<x-hoja-impresion>`, sin el layout de la app: **1. Fórmula y
Armas** (las dos pruebas LADO A LADO, 16 competidores, edad y país solo en fórmula,
resultados y jueces de la pista con Nivel·País por prueba, casillas de edad y categoría),
**2. Sparring** (tabla de libres, llave 16→8→4→2 con Puntos/Advertencias por ronda —
Primera ronda, Segunda ronda, Semifinales, Final—, finalistas por 1.º/2.º y 3.º/4.º,
resultados, registro de firmas y observaciones) y **3. Recuento de medallas** (por pista:
1.er, 2.º, 3.er lugar y participación). Los nombres de las pruebas y de los criterios de
cada juez también salen de la planilla (`Formula Tradicional`, `Armas Tradicionales`,
`Sparring`), por eso `CompetenciaSeeder` busca la prueba por `modalidad` y el criterio por
`papel_juez`: así el nombre se corrige sin duplicar ni perder los puntajes ya cargados. **Auditoría (Fase 7a):** `accesos_datos` + `BuscadorPersonas` (alta por
documento). **Roles por sede (Fase 7b):** `personal_grupo_rol` + rol `direccion-sede`;
el alcance por sede se aplica en las policies vía `User::sedesRestringidas()` (leyendo
`personal_grupo_rol`), **sin** activar el modo *teams* de spatie — el aislamiento por
grupo lo sigue dando la capa de tenancy. Pendiente (opcional): *teams* de spatie si
alguna vez se decide mover la autorización allí (hoy no es necesario).

## Roles y permisos

`admin-plataforma` (todo, cruza grupos) · `federacion` (solo lectura sobre
todos) · `direccion` (todo en su grupo, incl. pagos) · `direccion-sede` (lo
operativo de **su sede**, con pagos; acotado vía `personal_grupo_rol.sede_id` +
`User::sedesRestringidas()`) · `administrativo` (alumnos/clases/asistencia, **sin
pagos**) · `instructor` (asistencia, planificaciones, competencia, inscribir exámenes; ve
**solo alumnos de sus clases** vía `MatriculaPolicy` + `Matricula::scopeVisiblePara`) ·
`apoderado` (solo sus hijos, por tutela) · `alumno` (ver formación).

- **Permisos extra** (además de gestión/formación): `gestionar cuestionarios`
  (examinador) · `rendir cuestionarios` · `gestionar recompensas` · `ver recompensas`
  (alumno/apoderado) · `gestionar inscripciones` · `aprobar ascensos` (licenciatario:
  admin/dirección). Los permisos del módulo Programas son `ver programas`,
  `gestionar programas` (catálogo), `gestionar inscripciones` y `aprobar ascensos`.
- **Rol ≠ Rango**: el rol (spatie) da permisos; el **rango** (`cargos_rangos` →
  `users.rango_id`) es la jerarquía marcial ATA y **no da permisos**. Son ejes
  independientes (puede haber rango sin rol y viceversa).
- **2FA obligatoria** por rol: `config('bekho.2fa_obligatorio_para')` = `['admin-plataforma','direccion']`, middleware `ExigeDosFactores`.
- **Solo lectura** (federación): trait `App\Livewire\Concerns\SoloLectura` →
  `bloqueaSiSoloLectura()` en las acciones de escritura; `User::esSoloLectura()`.

## Módulos

- **Gestión**: alumnos (inscripción, instructor por sede), clases
  **multi-instructor** (pivote `clase_instructor`, papel titular/asistente/ayudante;
  nombre autocompletado) y **multi-horario** (`horarios_clase`: una clase de lunes y
  miércoles = una clase con dos horarios; `hora_fin` obligatoria y autocompletada a
  inicio+45; `cupo_maximo` como aforo que solo advierte), **asistencia como
  calendario semanal** (cada clase aparece en cada día en que tiene horario), pagos,
  exámenes (instructor inscribe; dirección finaliza), planificaciones de clase. La **inscripción**
  captura el consentimiento de uso de imagen y la homologación de grado, y genera el
  cobro de ingreso (matrícula + uniforme opcional) según la tarifa de la sede. **Ficha de alumno** (`estudiantes.ver`): progreso al
  siguiente cinturón, requisitos técnicos, historial de exámenes, asistencia, estado
  de cuenta y **notas del instructor** (`notas_matricula`). **Reportes**
  (`reportes.index`): distribución por cinturón, altas por mes y comparativa de
  sedes (activos/morosos/cobrado) con export CSV.
- **Programas (Formación + Legacy unificados)**: `programas` (catálogo de la
  federación: LMS "Aprender" + tracks de instructores) → `etapas_programa` →
  `contenidos` (estudio) + `requisitos_etapa`. El progreso de estudio cuelga de la
  **persona** (`progreso_contenidos.persona_id`; los menores no tienen cuenta). UI en
  `App\Livewire\Programas\*` (`/programas`): consumo (`ListaProgramas`/`VerPrograma`/
  `VerContenido`, navegación Anterior/Siguiente + "Completar y continuar →"; permiso
  `ver programas`), gestión de inscripciones (`GestionInscripciones`: inscribe personas,
  horas, requisitos y aprueba ascensos sobre `inscripciones_programa`; permiso
  `gestionar inscripciones`) y administración del catálogo (`Admin\AdminEtapas`/
  `AdminContenidos`; permiso `gestionar programas`). Aquí va el estudio de los manuales ATA:
  "Preparación para examen de juez" (Manual del Juez ATA en 18 secciones) y los manuales
  **Legacy, Tigers, MAK y MAX N1/N2** (`ManualesAprenderSeeder`, fuente en
  `database/data/manuales/*.json`, transcritos de los .docx). Cada manual = una etapa de
  su programa con una lección de texto por sección + el documento oficial en Drive.
  **Instrumentos de evaluación práctica** (`instrumentos_evaluacion`→`secciones`→
  `criterios`; `evaluaciones_practicas`→`puntajes_criterio`): prueba de planillero y
  evaluación de formas/patadas (ver "Estado y plan").
- **Currículo ATA (planificador)**: `Planificador` (planificación grupo×nivel o Cinturón
  Negro, calentamiento por clase, lección de vida; **week-aware**: sobre la planificación
  fija muestra la rotación del ciclo elegido — selector ciclo + bloque de semanas —
  leyendo `planner_ciclo`, y la lección de vida acotada a ese mismo ciclo. El planner
  regular y el de Cinturón Negro comparten estilo visual: tarjeta blanca con acento
  de color, no banners a sangre), **Ciclos** (`PlanCiclos`: class
  planner de cada ciclo — grilla fila × bloque de semanas — con sus lecciones de
  vida), **Biblioteca de técnicas** (patadas/manos/tricks/armas con pasos; las
  **formas** ya NO son técnicas), **Formas** (`Formas`: página propia, poomsae del
  Manual ATA Legacy con su secuencia paso a paso desde `formas`/`pasos_forma`),
  **Cuadrantes de Enseñanza**, **juramentos** (`juramentos`: Espíritu Songahm de
  inicio/cierre y juramento Tigers, confirmados por la federación; el Planificador
  los muestra al inicio y al cierre según la categoría del grupo —
  `App\Enums\CategoriaJuramento` Tigers / Kids y Adultos, `MomentoJuramento`
  inicio/cierre/ambos), **Cinturones** (`Cinturones`: escala de grados con
  color, `tipo` recomendado/decidido/dan, `franjas`, significado Songahm, las
  técnicas enlazadas por color vía `grado_tecnica` y la **comparativa de patadas
  BEKHO (examen) vs ATA (manual)** por grado). Eje: `Ciclo` = 6 Habilidades de
  Vida Songahm × 8 semanas; cada ciclo tiene su grilla en `planner_ciclo` (filas del
  enum `FilaPlannerCiclo`: Warm-Up/Kicks/Forms/Quadrants/Protech/Drills × bloques
  `1&2…7&8`). El **Class Planner físico de BEKHO** (láminas Beginners/Intermediate/
  Advanced) es esta misma rotación en formato BEKHO (áreas × 4 cuadrantes) y rota
  semana a semana: su contenido vive en los Ciclos + el Planificador, no en una
  página estática aparte.
- **Cuestionarios** (evaluaciones autocorregidas, catálogo transversal + intentos
  operativos): el examinador (`gestionar cuestionarios`) crea bancos genéricos
  (preguntas de una o varias correctas); cualquiera con `rendir cuestionarios` los
  rinde con puntaje y explicación por pregunta. Cada intento nace **en revisión**
  (enum `EstadoIntento`); el examinador decide **aprobar / volver a intentar** en
  "Resultados" (aprobar bajo el umbral exige justificación). Paneles: "Mis intentos"
  (alumno) y "Resultados" (examinador). Banco base sembrado: examen de Juez ATA
  (`App\Support\Cuestionarios\BancoJuez`).
- **Recompensas / gamificación** (catálogo transversal `recompensas` + `logros`
  operativos): un solo módulo cubre **Franjas de Conocimiento** (MAK), **Star Tag**
  (Tigers, acumulable) y **Coleccionables** (6, uno por Habilidad de Vida). El
  instructor (`gestionar recompensas`) otorga/quita en el panel (catálogo filtrado
  por grupo etario del alumno); alumno/apoderado ven su colección en "Mis logros"
  (`ver recompensas`).
- **Programa Legacy** (track de formación de instructores, N1-3): ya NO es un módulo
  aparte, es el programa "Legacy" dentro de **Programas**. Sus 3 etapas (100 h c/u,
  edades 13/16/18, 1.er Dan en la N3) y requisitos se siembran desde
  `database/data/legacy_niveles.json` (`EtapasProgramaSeeder`), más los **14 requisitos
  Protech** por nivel (4/6/4) desde `database/data/armas_protech.json` (verificados, con
  fuente; el arma va en el texto); un requisito puede
  enlazarse a un cuestionario (prueba escrita = intento aprobado). Operativo por persona:
  `inscripciones_programa`, `horas_programa`, `cumplimientos_requisito`, `ascensos_programa`.
  Las horas se registran a mano o desde la **asistencia como ayudante**
  (`asistencias_ayudante` + `ServicioHorasAyudante`, horas congeladas del horario).

## Convenciones

- Español en clases, campos, métodos y comentarios.
- **Blade + Livewire**; sin SPA ni API. Flux **free** (gotchas: props de `<select>`
  deben arrancar en `''` no `null`; `flux:checkbox` usa el prop `label`; un
  `flux:input`/`flux:select` con `label` auto-renderiza su error, y en una **fila
  horizontal** (`flex … items-end`) ese error estira el campo y desalinea la fila →
  ocultar el error inline con `[&_[data-flux-error]]:hidden` en la fila y mostrar el
  mensaje con `@error` debajo).
- **Responsive** (móvil): un `grid … sm:grid-cols-N` **sin `grid-cols-1` base** crea
  en móvil una columna `auto` (max-content) que ignora `truncate` y desborda a la
  derecha → siempre declarar `grid-cols-1` explícito. En grids de tarjetas con
  contenido colapsable usar `items-start` para que expandir una no estire a la vecina;
  tablas anchas siempre dentro de `overflow-x-auto`.
- **Tablas** (filtrar/ordenar/totales): trait `App\Livewire\Concerns\ConTabla`
  (incluye `ConOrden`) da la propiedad `buscar` (#[Url]) + `aplicarBusqueda($query,
  $columnas)` (LIKE portable; admite relaciones con punto, p. ej. `sede.nombre`) y
  `aplicarOrden(...)`. En la vista: `<x-tabla.buscador>` para el filtro de texto,
  columnas `flux:table.column sortable :sorted=... :direction=$ordenDir wire:click="ordenarPor('campo')"`
  para el orden asc/desc, y `<x-tabla.resumen :total=... etiqueta="…" :plural="…"
  :sumas="[['etiqueta'=>…, 'valor'=>…]]">` con el conteo de filas y las sumas de
  montos (el pluralizador automático es inglés → pasar `plural` en palabras
  españolas terminadas en consonante). El resumen va **ARRIBA de la tabla** (junto al
  buscador en una fila `flex … sm:justify-between`), no al pie, para que sea visible
  sin bajar toda la lista. En tablas paginadas usa `->total()`; las sumas se calculan
  sobre TODO el filtro (no solo la página).
- Enums en `App\Enums` (backed string) con método `etiqueta()`.
- Migraciones L13: clase anónima, `casts()` como método, tipos de retorno.
- **Tabla nueva**: ¿operativa? → `grupo_id` + trait `PerteneceGrupo`.
  ¿Contenido ATA compartido? → **sin** `grupo_id` (catálogo).
- **Sin cascadas sobre el historial** (`restrictOnDelete` + SoftDeletes): las FK que
  cuelgan del historial usan `restrictOnDelete`, así que un `forceDelete` de persona o
  matrícula con historial FALLA (a propósito): la baja es lógica (`Persona`/`Matricula`
  usan `SoftDeletes`; alumnos y traslados fijan estado `Retirada`). Con `restrictOnDelete`
  van `matriculas.persona_id`/`grupo_id` y todo lo colgado de la matrícula
  (`asistencias`, `cargos` —los `pagos` cuelgan de `cargos` vía `pago_cargo`—, `becas`,
  `suspensiones`, `notas_matricula`, `graduaciones`, exámenes `inscripciones`, `logros`) y de la
  persona (`documentos_persona`, `tutelas`, `instructores`, `personal_grupo`,
  `solicitudes_traslado`). Se dejan en `cascadeOnDelete` las cascadas de **composición**
  legítima (el hijo no existe sin su padre: `pago_cargo`, `personal_grupo_rol`, hijos de
  planilla de competencia, `bloques`/`cuadrantes` de planificación, catálogos colgados de
  la federación).
- **"Planilla" = solo competencia.** La planificación de clase se llama
  `PlanificacionClase` (tabla `planificaciones_clase`, con `BloquePlanificacion` y
  `CuadrantePlanificacion`; permiso `gestionar planificaciones`; `App\Livewire\
  Planificador`). Las "planillas" (`planillas_competencia`, `jueces_planilla`, etc.)
  son exclusivamente de competencia.
- **Procedencia del contenido.** El contenido carga `fuente`/`verificado` (bool).
  El del planificador (`planificaciones_clase`, `planificaciones_cinturon_negro`,
  `categorias_calentamiento`) fue **generado con IA** desde
  `docs/planificador-unificado.tsx` → `verificado=false`, y el Planificador muestra
  un aviso. En cambio, el contenido del **Manual ATA Legacy v4** (formas, catálogos
  de leyenda/habilidades/atributos/armas, Cuadrantes de Enseñanza, Programa Legacy)
  se siembra desde `database/data/*.json` con `fuente='Manual ATA Legacy v4 (julio
  2018)'` y `verificado=true` (`ManualLegacySeeder`, `FormasPasosSeeder`). Donde el
  manual no dice, se siembra `null`; no se inventa ni completa (p. ej. las 5 formas
  de cinturón negro que el manual nombra pero no detalla van sin pasos y
  `verificado=false`).
- **"Forma" ≠ "Técnica".** Una forma (poomsae) es una secuencia con nombre coreano,
  significado y grado: vive en `formas`/`pasos_forma` (`Forma`, `PasoForma`;
  `pasos_forma.posicion_id`→`posiciones`, la sección y los modificadores como texto).
  Una técnica es un movimiento (`tecnicas`). Las formas NO están en `tecnicas`.
- Tests **Pest** + `RefreshDatabase`; `beforeEach` siembra los seeders necesarios
  (`RolesPermisosSeeder`, etc.) + `app(PermissionRegistrar::class)->forgetCachedPermissions()`;
  `Grupo` tenant con `Tenant::set()/olvidar()`.
- Seeders **idempotentes** (`updateOrCreate`).

## Entorno y comandos

- PostgreSQL: `service postgresql start` (DB `bekho`, user `postgres`/`postgres`).
- `php artisan migrate:fresh --seed` · `php artisan test` · `vendor/bin/pint`.
- `npm run build` falla por egress a fonts.bunny.net → workaround: `vite.config.js`
  temporal sin el plugin `fonts` (restaurar después). Assets (`public/build`) están
  **gitignoreados** → hay que reconstruir para ver cambios (Tailwind v4 genera on-build).
- Navegador para screenshots: Chromium en `/opt/pw-browsers/chromium-1194/chrome-linux/chrome`,
  Playwright desde `/opt/node22/lib/node_modules/playwright/index.js` (import default).
- Si el contenedor se reinicia puede borrar `vendor/`, `.env` y `public/build`.
  Recuperar: `cp .env.example .env && php artisan key:generate`; reconstruir assets;
  y `composer install` — pero el proxy **bloquea descargas de archivos de GitHub**
  (solo `git`), así que usar `composer config -g use-github-api false` +
  `--prefer-source`. `phpstan` es dist-only (sin `source`) → único paquete que no baja;
  es solo análisis estático (no afecta tests/Pint), se puede omitir temporalmente.

## Estado y plan

Integración de los manuales ATA (Legacy, Tigers, MAK, MAX) **completa** (ver
**`docs/plan-integracion-manual-legacy.md`**). Hechas las 7 fases: (1) backbone
Ciclo, (2) biblioteca de técnicas, (3) enriquecer `Grado` (recomendado/decidido,
franjas, significado, enlace a técnicas), (4) Cuadrantes de Enseñanza, (5) class
planners de los 6 ciclos, (6) recompensas/gamificación, (7) Programa Legacy
operativo. Además: módulo de **Cuestionarios** autocorregidos con revisión del
examinador y el **Manual del Juez** en "Aprender".

**Currículo de patadas BEKHO vs ATA** (en Cinturones): los **9 grados de color
(9→1, Blanco a Rojo)** con sus patadas BEKHO dictadas por la escuela, grado por
grado (`PatadasGradoSeeder::bekho`), en comparación con la referencia ATA del
manual (`::ata`). Cada patada se ejecuta en 4 variantes (1-4) o 4 giros (A-D). El **Class Planner físico** (láminas
Beginners/Intermediate/Advanced) NO tiene página propia: su contenido vive en el
Planificador (estructura + rotación) y en Cinturones (patadas). El **Planificador
es week-aware**: sobre la planificación fija muestra la rotación del ciclo elegido
(`planner_ciclo`) y la Lección de Vida acotada a ese ciclo.

Notificaciones **en la app** (canal database, trait `Notifiable`): cuando el
examinador decide un intento, el alumno recibe `App\Notifications\IntentoDecidido`;
bandeja en `/notificaciones` (`App\Livewire\Notificaciones`) con badge en el sidebar.
La **prueba escrita Legacy N3** ya tiene banco real (`App\Support\Cuestionarios\
BancoLegacy`, fundado en el Manual Legacy) enlazado al requisito automático.

Lecciones de vida sembradas (reales, aportadas por la escuela; `PlanificadorSeeder::
sembrarLecciones`): **Ciclo 1 Disciplina · Semana 7** y el **Ciclo 3 Comunicación
completo (Semanas 1–8)** (9 de 48). Cada una con los 3 momentos (comienzo/durante/
fin + frase).

**Unificación LMS + Legacy → programas/etapas (EN CURSO).** El LMS (`niveles`→
`contenidos`→`progreso_contenidos` por usuario) y el Programa Legacy (`niveles_legacy`
→`requisitos_legacy`, `inscripciones_legacy`/`horas_legacy` por usuario) eran dos
modelos paralelos para lo mismo. Se unen bajo `programas` (catálogo de la federación,
ahora con `federacion_id`, `grado_minimo_id` de ingreso, `fuente`/`verificado`) →
`etapas_programa` (fusión de niveles LMS + niveles_legacy: `nombre`, `orden`,
`horas_requeridas`, `edad_minima`, `grado_minimo_id`, `fuente`/`verificado`) →
`requisitos_etapa` (`App\Enums\TipoRequisitoEtapa` manual/cuestionario) y `contenidos`
/`cuestionarios` con `etapa_programa_id`. Motivo de fondo: **los menores no tienen
cuenta**, así que todo lo formativo debe colgar de `persona_id`, no de `user_id`.
Plan por commits (cada uno en verde): **(a) HECHO** — etapas y catálogo: migración
ADITIVA (expand; no se toca `niveles`/`nivel_id`) + `EtapasProgramaSeeder` idempotente
que ancla programas a la federación, siembra las 3 etapas Legacy desde
`database/data/legacy_niveles.json` (100 h, 13/16/18, 1.er Dan en la etapa 3, requisitos
+ prueba escrita enlazada al banco N3) y fusiona cada `nivel` del LMS en una etapa por
manual (Tigers/MAK/MAX→Xtreme/Juez; Legacy reutiliza el suyo; el resto va al contenedor
"Aprender (general)"), enlazando los contenidos. **(b) HECHO** — lo operativo pasa a la
PERSONA: nuevas `inscripciones_programa` (transversal, sin grupo_id), `horas_programa`,
`cumplimientos_requisito`, `ascensos_programa`, y `progreso_contenidos` gana `persona_id`
+ `registrado_por_user_id` (un hook autocompleta persona desde el user al crear).
`App\Support\Formacion\MigraFormacionAPersona` traslada los datos viejos sin pérdida
(user→persona; horas manuales congeladas; un user sin persona queda fuera y se reporta;
cumplimientos best-effort al resembrar los requisitos). **Horas por asistencia (follow-up
HECHO):** `asistencias_ayudante` (persona×clase×fecha, marca por sesión, operativo) +
`ServicioHorasAyudante`: al marcar a un trainee como ayudante de una clase en una fecha se
CONGELAN las horas (= suma de la duración de los horarios de esa clase ese día) y se
acreditan a su inscripción En curso cuya etapa exige horas (`horas_programa` con
`origen='asistencia'` y `asistencia_ayudante_id`); no se recalculan si cambia el horario, y
quitar la marca elimina su hora. Se registra desde `GestionInscripciones` (permiso
`gestionar inscripciones`). Conviven con las horas manuales. **(c) HECHO** —
instrumentos de evaluación práctica: `instrumentos_evaluacion` (catálogo de la
federación; escala + `puntaje_maximo`; aprueba por `umbral_porcentaje` O `nota_minima`,
alternativos según la escala) → `secciones_instrumento` → `criterios_instrumento`
(criterio puede atarse a un `atributo_tecnico`); `evaluaciones_practicas` (historial de
la persona, transversal) → `puntajes_criterio`; `planillas_competencia` gana
`evaluacion_practica_id`. `InstrumentosEvaluacionSeeder` siembra la **Prueba de
planillero** (escala Rúbrica 0–6.0, umbral 80 %, secciones Fórmula y Armas / Sparring /
Recuento de medallas) y la **Evaluación de formas y patadas** (escala Competencia 9.1–9.9,
nota mínima 9.5; 13 criterios = 10 atributos + 3 criterios de conocimiento de
`atributos_tecnicos`). **(d) HECHO** — retiro del modelo viejo y UI unificada: se
eliminaron `Nivel`/`NivelLegacy`/`InscripcionLegacy`/`RequisitoLegacy` y las tablas
`niveles`/`niveles_legacy`/`requisitos_legacy`/`inscripciones_legacy`/`horas_legacy`/
`cumplimiento_requisitos`; `contenidos` pasó a catálogo (sin `grupo_id`, bajo su etapa)
y `progreso_contenidos` a colgar de `persona_id`. Los seeders (`ManualesAprender`,
`PreparacionJuez`, `FormacionDemo`) crean programa→etapa→contenido directamente. **Aprender
y Legacy dejan de ser módulos separados: son el módulo "Programas"** (`App\Livewire\
Programas\*`, rutas `/programas`): `ListaProgramas`, `VerPrograma`, `VerContenido`
(consumo, permiso `ver programas`); `GestionInscripciones` (antes PanelLegacy; permiso
`gestionar inscripciones`) opera sobre `inscripciones_programa`; `Admin\AdminEtapas`/`AdminContenidos`
(`gestionar programas`). Los permisos `ver formacion`/`gestionar formacion`/`gestionar
legacy`/`aprobar legacy` se renombraron a `ver programas`/`gestionar programas`/`gestionar
inscripciones`/`aprobar ascensos`. El **ascenso** lo aprueba el instructor de la matrícula activa
del alumno (`matriculas.instructor_persona_id`) o, en su defecto, dirección/licenciatario
(`aprobar legacy`); la supervisión es solo informativa. `ServicioProgramas` (antes
`ServicioFormacion`) maneja el progreso por persona. **(d)** retirar `Nivel`/`NivelLegacy`/`Inscripcion
Legacy`/`RequisitoLegacy` y unificar la UI (Aprender y Legacy pasan a ser programas).

Pendiente / ideas (requieren datos reales de la escuela, no se inventan):
**sembrar el resto de lecciones de vida** (46 semanas · el usuario pasa la lámina y
se agrega a la lista de `sembrarLecciones`).
