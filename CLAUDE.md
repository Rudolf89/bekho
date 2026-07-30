# CLAUDE.md — Sistema BEKHO

Guía para trabajar en este repo. **Todo el código y el dominio están en español.**

## Qué es

Plataforma de **gestión + formación** para la **federación BEKHO** de Taekwondo ATA
(Chile). Administra cada academia (alumnos, sedes, clases, asistencia, pagos,
exámenes, planillas) y ofrece formación en línea (LMS) + el currículo ATA.

## Stack

- **Laravel 13** (PHP 8.3+), monolito, **sin API**.
- **Livewire 4 + Flux UI (versión FREE)** + **Tailwind CSS 4**. Toda la UI es Blade+Livewire.
- **PostgreSQL**. Tests en SQLite in-memory.
- **Laravel Fortify** (login, 2FA, passkeys, reset).
- **spatie/laravel-permission** (roles/permisos, **sin teams mode**).

## Jerarquía y tenancy (clave)

**BEKHO es la FEDERACIÓN (la plataforma), no una academia.** Jerarquía real:
`BEKHO (federación) → Academia = GRUPO (p. ej. "BEKHO Power Academy") → Sede → Clase`.
El **aislamiento entre grupos es total**: `academia_id` **es la frontera real**, no
una costura futura.

Piezas del tenant:
- `App\Support\Tenancy\Academia` — holder estático: `set(?int $id, bool $filtraLecturas = true)`, `id()`, `hayActiva()`, `filtraLecturas()`, `olvidar()`.
- `App\Models\Concerns\PerteneceAcademia` — trait: global scope que filtra por
  `academia_id` **si hay academia activa y `filtraLecturas()`**; autorellena
  `academia_id` al crear; scope `sinAcademia()`.
- `App\Http\Middleware\EstableceAcademiaActual` — fija el tenant. Un rol normal
  queda atado a su `academia_id`. El **`admin-plataforma`** elige academia en el
  selector: si elige una, la vista se **acota** (`filtraLecturas: true`); si elige
  "Todas las academias", ve todo (`filtraLecturas: false`). La **`federacion`** ve
  todo en **solo lectura**.

**Catálogos compartidos = SIN `academia_id`** (como `cargos_rangos`): `planillas`,
`bloques_planilla`, `cuadrantes_planilla`, `ciclos`, `planner_ciclo`, `tecnicas`,
`pasos_tecnica`, `cuadrante_items`, `grados`, `grado_tecnica`, `curriculos_nivel`,
`lecciones_vida`, biblioteca de calentamiento, planificador de Cinturón Negro,
`cuestionarios`, `preguntas_cuestionario`, `opciones_pregunta`, `recompensas`,
`niveles_legacy`, `requisitos_legacy`. **Datos operativos = CON `academia_id`**:
usuarios, sedes, alumnos, clases, asistencia, pagos, exámenes, `calentamiento_clase`,
`intentos_cuestionario`, `logros`, `inscripciones_legacy`, `horas_legacy`.

## Roles y permisos

`admin-plataforma` (todo, cruza academias) · `federacion` (solo lectura sobre
todas) · `direccion` (todo en su academia, incl. pagos) · `administrativo`
(alumnos/clases/asistencia, **sin pagos**) · `instructor` (asistencia, planillas,
inscribir exámenes; ve **solo alumnos de sus clases** vía `EstudiantePolicy` +
`Estudiante::scopeVisiblePara`) · `apoderado` (solo sus hijos) · `alumno` (ver
formación).

- **Permisos extra** (además de gestión/formación): `gestionar cuestionarios`
  (examinador) · `rendir cuestionarios` · `gestionar recompensas` · `ver recompensas`
  (alumno/apoderado) · `gestionar legacy` · `aprobar legacy` (licenciatario:
  admin/dirección).
- **Rol ≠ Rango**: el rol (spatie) da permisos; el **rango** (`cargos_rangos` →
  `users.rango_id`) es la jerarquía marcial ATA y **no da permisos**. Son ejes
  independientes (puede haber rango sin rol y viceversa).
- **2FA obligatoria** por rol: `config('bekho.2fa_obligatorio_para')` = `['admin-plataforma','direccion']`, middleware `ExigeDosFactores`.
- **Solo lectura** (federación): trait `App\Livewire\Concerns\SoloLectura` →
  `bloqueaSiSoloLectura()` en las acciones de escritura; `User::esSoloLectura()`.

## Módulos

- **Gestión**: alumnos (inscripción, instructor por sede), clases
  **multi-instructor** (pivote `clase_instructor`, papel titular/asistente/ayudante;
  nombre y hora fin autocompletados), **asistencia como calendario semanal**, pagos,
  exámenes (instructor inscribe; dirección finaliza), planillas.
- **Formación / LMS ("Aprender")**: niveles → contenidos → progreso por usuario.
  Aquí va también el material de negocio/marketing de los manuales ATA y el estudio
  (p. ej. "Preparación para examen de juez" = Manual del Juez ATA en 18 secciones).
- **Currículo ATA (planificador)**: `Planificador` (planilla grupo×nivel o Cinturón
  Negro, calentamiento por clase, lección de vida), **Ciclos** (`PlanCiclos`: class
  planner de cada ciclo — grilla fila × bloque de semanas — con sus lecciones de
  vida), **Biblioteca de técnicas** (patadas/formas/manos/tricks/armas con pasos),
  **Cuadrantes de Enseñanza**, **Cinturones** (`Cinturones`: escala de grados con
  color, `tipo` recomendado/decidido/dan, `franjas`, significado Songahm y las
  técnicas enlazadas por color vía `grado_tecnica`). Eje: `Ciclo` = 6 Habilidades de
  Vida Songahm × 8 semanas; cada ciclo tiene su grilla en `planner_ciclo` (filas del
  enum `FilaPlannerCiclo`: Warm-Up/Kicks/Forms/Quadrants/Protech/Drills × bloques
  `1&2…7&8`). **Class Planner** (`ClassPlanners`: reproducción de los tres
  planificadores de clase oficiales BEKHO — Beginners/Intermediate/Advanced —, cada
  área × los 4 Cuadrantes de Enseñanza; referencia de solo lectura, sin persistencia).
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
- **Programa Legacy** (track de formación de instructores, N1-3): catálogo
  `niveles_legacy` (100 h c/u) + `requisitos_legacy` (un requisito puede enlazarse a
  un cuestionario → se cumple con un intento aprobado = prueba escrita). Operativo:
  `inscripciones_legacy`, `horas_legacy` y cumplimiento por inscripción. El panel
  registra horas (barra a 100 h) y requisitos; el **licenciatario** (`aprobar
  legacy`) aprueba el ascenso cuando se cumplen horas y requisitos.

## Convenciones

- Español en clases, campos, métodos y comentarios.
- **Blade + Livewire**; sin SPA ni API. Flux **free** (gotchas: props de `<select>`
  deben arrancar en `''` no `null`; `flux:checkbox` usa el prop `label`).
- Enums en `App\Enums` (backed string) con método `etiqueta()`.
- Migraciones L13: clase anónima, `casts()` como método, tipos de retorno.
- **Tabla nueva**: ¿operativa? → `academia_id` + trait `PerteneceAcademia`.
  ¿Contenido ATA compartido? → **sin** `academia_id` (catálogo).
- Tests **Pest** + `RefreshDatabase`; `beforeEach` siembra los seeders necesarios
  (`RolesPermisosSeeder`, etc.) + `app(PermissionRegistrar::class)->forgetCachedPermissions()`;
  `Academia` tenant con `Tenant::set()/olvidar()`.
- Seeders **idempotentes** (`updateOrCreate`).

## Entorno y comandos

- PostgreSQL: `service postgresql start` (DB `bekho`, user `postgres`/`postgres`).
- `php artisan migrate:fresh --seed` · `php artisan test` · `vendor/bin/pint`.
- `npm run build` falla por egress a fonts.bunny.net → workaround: `vite.config.js`
  temporal sin el plugin `fonts` (restaurar después). Assets (`public/build`) están
  **gitignoreados** → hay que reconstruir para ver cambios (Tailwind v4 genera on-build).
- Navegador para screenshots: Chromium en `/opt/pw-browsers/chromium-1194/chrome-linux/chrome`,
  Playwright desde `/opt/node22/lib/node_modules/playwright/index.js` (import default).

## Estado y plan

Integración de los manuales ATA (Legacy, Tigers, MAK, MAX) **completa** (ver
**`docs/plan-integracion-manual-legacy.md`**). Hechas las 7 fases: (1) backbone
Ciclo, (2) biblioteca de técnicas, (3) enriquecer `Grado` (recomendado/decidido,
franjas, significado, enlace a técnicas), (4) Cuadrantes de Enseñanza, (5) class
planners de los 6 ciclos, (6) recompensas/gamificación, (7) Programa Legacy
operativo. Además: módulo de **Cuestionarios** autocorregidos con revisión del
examinador y el **Manual del Juez** en "Aprender".

Pendiente / ideas: sembrar un banco de preguntas real para la prueba escrita
Legacy N3 (hoy el requisito es checklist; el enlace a cuestionario ya existe);
notificar al alumno cuando el examinador decide un intento; volcar más
negocio/marketing a "Aprender".
