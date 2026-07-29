# Sistema BEKHO

Plataforma de **gestión y formación** para las academias de Taekwondo ATA de la
**federación BEKHO** en Chile. Reúne en un solo lugar la administración de cada academia
(alumnos, sedes, clases, asistencia, pagos, exámenes) y la formación en línea de sus
miembros (LMS).

El código y el dominio están escritos **en español**.

## Estado actual

| Fase | Módulo | Estado |
|------|--------|--------|
| Fase 1 | Núcleo transversal (academias, sedes, rangos, roles/permisos, tenancy) | **Hecho** |
| Fase 2 | Formación / LMS (niveles → contenidos → progreso por usuario) | **Hecho** |
| Fase 3 | Gestión de alumnos, clases (multi-instructor), asistencia (calendario) y pagos | **Hecho** |
| Fase 3 | Exámenes de grado (inscripción, resultados, conteo en cascada) | **Hecho** |
| Fase 3 | Planillas de clase | **Hecho** |
| Fase 4 | Currículo ATA: Ciclos y class planners, biblioteca de técnicas, cuadrantes de enseñanza, cinturones (recomendado/decidido, franjas, significado, técnicas por grado) | **Hecho** |
| Fase 5 | Cuestionarios autocorregidos (banco genérico, con revisión del examinador) + Manual del Juez en "Aprender" | **Hecho** |
| Fase 6 | Recompensas / gamificación (Franjas de Conocimiento, Star Tag, Coleccionables) | **Hecho** |
| Fase 7 | Programa Legacy operativo (Niveles 1-3, 100 h, requisitos, ascenso, prueba escrita) | **Hecho** |

---

## Stack

- **Laravel 13** (PHP 8.3+) — monolito, **sin API separada**.
- **Livewire 4** + **Flux UI** + **Tailwind CSS 4** — toda la interfaz es Blade + Livewire.
- **PostgreSQL** como base de datos.
- **Laravel Fortify** para autenticación (login, registro, 2FA, passkeys, recuperación).
- **spatie/laravel-permission** para roles y permisos (**sin teams mode**; catálogo fijo
  compartido).

No hay frontend SPA ni API REST/GraphQL. Si a futuro se necesita un cliente nativo
(app móvil, etc.), se añadirá **Laravel Sanctum**; hoy no está y no hace falta.

---

## Requisitos

- **PHP 8.3+** con las extensiones habituales de Laravel **más `pdo_pgsql` y `pgsql`**.
- **Composer**.
- **Node 20+** y **npm**.
- **PostgreSQL 15+**.

### Entorno local: Laragon en Windows

El entorno de desarrollo de referencia es **Laragon en Windows**. Un detalle que costó
al instalar: las extensiones de PostgreSQL **no vienen activas por defecto**. Edita tu
`php.ini` (el que usa Laragon; verifícalo con `php --ini`) y descomenta:

```ini
extension=pdo_pgsql
extension=pgsql
```

Reinicia Laragon después. Comprueba con:

```bash
php -m | findstr pgsql
```

Debe listar `pdo_pgsql` y `pgsql`.

---

## Instalación paso a paso

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repo> bekho
cd bekho

composer install
npm install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` para apuntar a PostgreSQL (el kit viene con `sqlite` por defecto):

```dotenv
APP_NAME=BEKHO
APP_URL=http://bekho.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bekho
DB_USERNAME=bekho
DB_PASSWORD=una-clave-segura
```

### 3. Crear la base y los permisos de esquema

Crea la base y su usuario:

```sql
CREATE DATABASE bekho;
CREATE USER bekho WITH PASSWORD 'una-clave-segura';
```

**Importante (PostgreSQL 15+):** desde la versión 15, el rol dueño de la base no tiene
por defecto permisos de escritura sobre el esquema `public`, y las migraciones fallan con
un error de permisos. Conéctate a la base `bekho` **como superusuario** y ejecuta:

```sql
\c bekho
GRANT ALL ON SCHEMA public TO bekho;
ALTER SCHEMA public OWNER TO bekho;
```

(Este fue un problema real durante la instalación; sin esto `migrate` no corre.)

### 4. Migrar y sembrar

```bash
php artisan migrate --seed
```

Esto crea el esquema y siembra:

- El catálogo de **cargos/rangos** (7 rangos ATA).
- Los **roles y permisos**.
- La primera academia (grupo): **BEKHO Power Academy**.
- Un usuario **administrador de plataforma** (ver credenciales abajo).
- Datos de **demostración** (`DemoBekhoSeeder`): alumnos, clases, asistencia y pagos de
  ejemplo para que el panel se vea "vivo". Se puede quitar del `DatabaseSeeder` antes de
  producción.

### 5. Levantar el frontend

```bash
npm run dev
```

Y sirve la app con Laragon (dominio `bekho.test`) o con `php artisan serve`.

---

## Credenciales de desarrollo

El seeder crea un administrador de plataforma:

| Campo | Valor |
|-------|-------|
| Email | `admin@bekho.cl` |
| Contraseña | `cambiar-esto` |
| Rol | `admin-plataforma` |
| `academia_id` | `null` (ve todas las academias) |

> ⚠️ **Cambia esta contraseña de inmediato** en cualquier entorno que no sea tu máquina
> local. Nunca despliegues con estas credenciales por defecto.

> El rol `admin-plataforma` tiene **2FA obligatoria**. En desarrollo puedes marcar la
> cuenta como confirmada sin configurar TOTP con un `UPDATE users SET
> two_factor_confirmed_at = now() WHERE email = 'admin@bekho.cl';`, o iniciar sesión con
> una cuenta de rol `instructor`/`administrativo` (sin 2FA obligatoria).

---

## Arquitectura

### Jerarquía organizacional

**BEKHO es la federación (la plataforma), no una academia.** La jerarquía real es:

```
BEKHO (federación, no es un registro en la BD)
 └── Academia   = cada GRUPO (p. ej. "BEKHO Power Academy", "BEKHO Pride Academy")
      └── Sede  = lugar físico (academia abierta, club, colegio, jardín)
           └── Clase
```

El **aislamiento entre grupos es total**: un maestro de un grupo **no** ve los alumnos,
pagos ni datos de otro grupo. Por eso `academia_id` **no es una costura para el futuro: es
la frontera real y se usa desde ya**. El seeder crea el primer grupo ("BEKHO Power
Academy"); los demás (Pride, IV Región, Strike, …) aún no se confirman y se agregan cuando
existan.

### Aislamiento por academia (tenancy)

El aislamiento se apoya en tres piezas:

1. **`App\Support\Tenancy\Academia`** — contenedor estático de la academia activa durante
   la petición (`set(?int, bool $filtraLecturas = true)`, `id()`, `hayActiva()`,
   `filtraLecturas()`, `olvidar()`).
2. **`App\Models\Concerns\PerteneceAcademia`** — trait que se agrega a los modelos con
   `academia_id`. Hace dos cosas:
   - Añade un **global scope** que filtra `where academia_id = <activa>` **si hay academia
     activa y `filtraLecturas()` es true**.
   - En `creating`, **autorellena** `academia_id` con la academia activa si el modelo no la
     trae.
   - Expone el scope `sinAcademia()` para saltarse el filtro cuando haga falta.
3. **`App\Http\Middleware\EstableceAcademiaActual`** — corre después de autenticar. Fija la
   academia activa según el usuario:
   - Un rol normal (dirección, instructor, …) queda atado a **su** `academia_id`.
   - El **`admin-plataforma`** elige academia en el selector del sidebar. Si elige una, la
     vista se **acota** a ella (`filtraLecturas: true`); si elige **"Todas las academias"**,
     ve todo el sistema (`filtraLecturas: false`) con la primera academia como contexto de
     creación.
   - La **`federacion`** supervisa todas las academias en modo solo lectura
     (`filtraLecturas: false`).

### Catálogos compartidos

Algunas tablas son transversales a todas las academias y **NO llevan `academia_id`**. El
caso central es **`cargos_rangos`** (el escalafón ATA): es un catálogo fijo compartido, así
que su modelo `CargoRango` **no usa** el trait `PerteneceAcademia`.

Los **rangos** sembrados (nivel 1 = más alto):

| Nivel | Rango | Grado Dan | Distintivo |
|-------|-------|-----------|------------|
| 1 | Gran Maestro | 9 | Gala Negro/Oro |
| 2 | Maestro Jefe | 8 | Gala Rojo |
| 3 | Maestro Sénior | 7 | Gala Azul |
| 4 | Maestro | 6 | Gala Blanco |
| 5 | Profesor | — | Collar Negro sólido |
| 6 | Instructor | — | Collar Negro/Rojo/Negro |
| 7 | Legado (Ayudante) | — | Collar Rojo |

### Rol y Rango son ejes independientes

- El **Rango** (`cargos_rangos` → `users.rango_id`) es la **jerarquía marcial** de ATA:
  "quién eres". **No otorga permisos**; se usa para el escalafón, el conteo en cascada y los
  distintivos. La tabla anterior es este catálogo (el rango "Legado/Ayudante" sigue vigente).
- El **Rol** (spatie) son los **permisos en el software**: "qué puedes hacer".

Son independientes: puede haber alguien con rango y **sin** rol operativo (p. ej. un maestro
que no gestiona en el sistema), y alguien con rol y **sin** rango (una secretaria). En la
gestión de usuarios se editan como **campos separados**.

### Roles y permisos

Roles (spatie, **sin teams mode** — los roles son globales):

| Rol | Quién | Permisos |
|-----|-------|----------|
| `admin-plataforma` | Dueño del sistema | Todos, incl. `gestionar academias`; cruza academias |
| `federacion` | Casa Central | Solo lectura sobre **todas** las academias |
| `direccion` | Director de un grupo | Todo dentro de **su** academia (usuarios, sedes, alumnos, clases, asistencia, **pagos**, planillas, formación) |
| `administrativo` | Secretaría / recepción | Alumnos, clases, asistencia. **Sin pagos** |
| `instructor` | Enseña clases | Asistencia, planillas, **inscribir en exámenes**, ver formación; ve **solo los alumnos de sus clases** |
| `apoderado` | Apoderado | Ninguno global; ve solo a sus hijos (Policies) |
| `alumno` | Alumno | `ver formacion` |

Permisos definidos: `gestionar academias`, `gestionar usuarios`, `gestionar sedes`,
`gestionar alumnos`, `gestionar clases`, `tomar asistencia`, `registrar pagos`,
`gestionar examenes`, `inscribir examenes`, `gestionar planillas`, `gestionar formacion`,
`ver formacion`.

Reglas clave:

- **Pagos** solo para `direccion` y `admin-plataforma` (los instructores no reciben dinero).
- **Exámenes**: el `instructor` inscribe; el alumno queda inscrito **sin aprobación** de
  nadie. Editar resultados/notas y finalizar es de gestión (`gestionar examenes`).
- La **federación** ve todo pero **no escribe** (helper `User::esSoloLectura()` + guard en
  los componentes de escritura).
- El **instructor** ve/edita solo los alumnos de las clases donde está asignado; se resuelve
  con **Policies** (`EstudiantePolicy` + scope `Estudiante::scopeVisiblePara`), no filtrando
  en la vista.

---

## Módulos

**Núcleo (Fase 1)**
- Academias, sedes, catálogo de rangos.
- **Sedes** con `tipo` (`App\Enums\TipoSede`: academia / club / colegio / jardín) y
  `privada` (solo miembros de la entidad). Multi-sede por persona vía pivote `sede_user`.
- Usuarios extendidos: `academia_id`, `rango_id`, `supervisor_id` (jerarquía), `telefono`,
  `activo`. Rol y rango como ejes independientes.
- Tenancy por academia, roles/permisos y **2FA obligatoria por rol**.

**Formación / LMS (Fase 2)**
- Estructura **niveles → contenidos → progreso por usuario**.
- Un **contenido** vive en **un solo nivel** (no se comparte entre niveles).
- El **progreso** por usuario guarda **solo el estado** (avance del alumno), no una copia
  del contenido.
- Los **videos se alojan externamente** (no se sirven desde la app); el LMS guarda la
  referencia, no el archivo.

**Gestión (Fase 3)**
- **Alumnos**: ficha completa, inscripción con validación (apoderado según grupo etario,
  comuna por región, día de vencimiento). Al elegir sede, los instructores disponibles son
  los asignados a esa sede.
- **Clases**: una clase puede tener **varios instructores** con su papel (titular /
  asistente / ayudante) vía pivote `clase_instructor`. El **nombre** y la **hora de fin**
  (inicio + 45 min) se **autocompletan** y quedan editables.
- **Asistencia**: **calendario semanal** con navegación por semanas; cada día muestra sus
  clases con horario, instructor y avance (presentes/esperados).
- **Pagos**: mensualidades, morosidad y descuento por hermanos.
- **Exámenes** de grado: convocatorias, inscripción por el instructor, resultados y
  **conteo en cascada** por la línea de supervisión (collares de máster).
- **Planillas** de clase (rutinas por grupo/nivel con estructura de bloques).

**Currículo ATA (Fase 4)**

Contenido pedagógico transversal a todas las academias (catálogos compartidos, sin
`academia_id`), organizado en torno al **Ciclo** = una de las 6 Habilidades para la
Vida Songahm (Disciplina, Convicción, Comunicación, Respeto, Autoestima, Honestidad),
de 8 semanas.

- **Ciclos** (`ciclos`, `planner_ciclo`): cada ciclo tiene su **class planner** — una
  grilla por fila (Warm-Up, Kicks, Forms, Quadrants, Protech, Drills en pareja) y
  bloque de semanas (`1&2`, `3&4`, `5&6`, `7&8`) — más sus **lecciones de vida** por
  semana. Transcrito del Manual Legacy.
- **Planificador**: planilla grupo × nivel (o de Cinturón Negro), con calentamiento
  por clase y lección de vida.
- **Biblioteca de técnicas** (`tecnicas`, `pasos_tecnica`): patadas, formas, técnicas
  de mano, tricks y armas, con sus pasos por segmento y filtros por categoría/modalidad.
- **Cuadrantes de Enseñanza** (`cuadrante_items`): marco pedagógico ATA con las
  responsabilidades del alumno y del instructor por cuadrante.
- **Cinturones** (`grados`, `grado_tecnica`): escala de grados por programa con su
  color, `tipo` (recomendado / decidido / dan), `franjas` (barras del cinturón; los
  danes llevan una por grado), el significado Songahm y las **técnicas enlazadas** a
  cada cinturón (normalizando grafías: Morado↔Púrpura, Camuflaje↔Camuflado,
  Marrón↔Café).

**Cuestionarios (Fase 5)**

Módulo de evaluaciones **autocorregidas** y configurable. Catálogo transversal
(`cuestionarios`, `preguntas_cuestionario`, `opciones_pregunta`); los intentos son
operativos (`intentos_cuestionario`).

- El **examinador** crea bancos genéricos (preguntas de una o varias respuestas
  correctas). Cualquiera con permiso los **rinde** con puntaje y explicación por
  pregunta.
- Cada intento nace **en revisión**; el examinador decide **aprobar** o **volver a
  intentar** en "Resultados" (aprobar por debajo del umbral exige justificación).
  El alumno ve su historial en "Mis intentos".
- Banco base: el **examen de Juez ATA** (N1/N2/N3 + repaso). El **Manual del Juez**
  (18 secciones) se carga como estudio en "Aprender".

**Recompensas / gamificación (Fase 6)**

Un solo módulo (catálogo transversal `recompensas` + `logros` operativos) cubre los
tres sistemas de los manuales: **Franjas de Conocimiento** (MAK), **Star Tag**
(Tigers, acumulable) y **Coleccionables** (6, uno por Habilidad de Vida). El
instructor otorga/quita logros (catálogo filtrado por el grupo etario del alumno);
el alumno/apoderado ve su colección en **"Mis logros"**.

**Programa Legacy (Fase 7)**

Track de formación de instructores (Niveles 1-3). Catálogo transversal
(`niveles_legacy` de 100 h + `requisitos_legacy`) y datos operativos
(`inscripciones_legacy`, `horas_legacy`). Se registran horas (barra de avance a las
100 h) y requisitos; el **licenciatario** aprueba el **ascenso** cuando se cumple
todo. Un requisito puede enlazarse a un **cuestionario**: entonces la **prueba
escrita** se da por cumplida con un intento aprobado.

---

## Seguridad

Resumen del estado real de las funciones que trae el starter kit (auditadas sobre el
código). Varias vienen "andamiadas" pero no todas están activas.

| Función | Estado hoy | Nota |
|---------|-----------|------|
| **Login rate limiting** | **Activo** | 5/min por email+IP; 2FA 5/min; passkeys 10/min |
| **2FA (TOTP)** | **Obligatoria por rol** | Exigida a `admin-plataforma` y `direccion` (`config/bekho.php` → `2fa_obligatorio_para`, middleware `ExigeDosFactores`); opcional para el resto |
| **Passkeys (WebAuthn)** | Disponible, opcional | Alternativa sin contraseña |
| **Recuperación de contraseña** | Activo | Requiere `MAIL_*` configurado en producción |
| **Confirmación de contraseña** | Activo | Protege la pantalla de seguridad (timeout 3 h) |
| **Verificación de email** | **Andamiada, inactiva** | Falta `implements MustVerifyEmail` en `User`; el middleware `verified` no bloquea |
| **Política de contraseñas** | Fuerte **solo en producción** | 12 + símbolos + chequeo HIBP; en local, mínimo 8 |
| **Cookie de sesión segura** | **No forzada** | Poner `SESSION_SECURE_COOKIE=true` + HTTPS en producción |
| **Auto-registro público** | **Abierto** | `/register` acepta a cualquiera |

### Pendiente de decidir (antes de producción)

- **Cerrar o restringir el auto-registro**: en una escuela las cuentas las crea la
  dirección/administración.
- **Forzar la cookie segura** y HTTPS.
- **Verificación de email**: activarla cuando el flujo de altas lo justifique (no urgente si
  las cuentas las controla la escuela).

> **2FA obligatoria por rol** ya está implementada (`admin-plataforma` y `direccion`),
> dejando el resto opcional: hay **datos de menores**, así que proteger las cuentas
> privilegiadas importa, sin obligar 2FA a un alumno de 12 años.

Detalle completo en la auditoría del proyecto.

---

## Comandos útiles

```bash
# Migrar
php artisan migrate

# Migrar y sembrar desde cero (¡borra datos!)
php artisan migrate:fresh --seed

# Solo sembrar
php artisan db:seed

# Un seeder puntual
php artisan db:seed --class=RolesPermisosSeeder

# Tests
php artisan test

# Lint (Pint) y análisis estático (PHPStan/Larastan)
composer lint
composer types:check

# Frontend en desarrollo
npm run dev

# Build de producción
npm run build
```

---

## Convenciones del proyecto

- **Código y dominio en español**: nombres de clases, campos, métodos y comentarios.
- **Blade + Livewire** para toda la interfaz. No hay SPA ni API por ahora.
- **Sin API**: si a futuro se necesita un cliente nativo, se añade **Sanctum** en ese
  momento; no se anticipa.
- **Migraciones** con clase anónima, `casts()` como método y tipos de retorno (estilo
  Laravel 13).
- **Tenancy**: cualquier tabla nueva del dominio lleva `academia_id` y su modelo usa el
  trait `PerteneceAcademia`, salvo que sea un **catálogo compartido** (entonces va sin
  `academia_id` y sin el trait).
