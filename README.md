# Sistema BEKHO

Plataforma de **gestión y formación** para una escuela de Taekwondo ATA en Santiago de
Chile. BEKHO reúne en un solo lugar la administración de la escuela (alumnos, sedes,
rangos) y la formación en línea de sus miembros (LMS).

El código y el dominio están escritos **en español**.

## Estado actual

| Fase | Módulo | Estado |
|------|--------|--------|
| Fase 1 | Núcleo transversal (academias, sedes, rangos, roles/permisos, tenancy) | **Hecho** |
| Fase 2 | Formación / LMS (niveles → contenidos → progreso por usuario) | **Hecho** |
| — | Gestión de alumnos, asistencia y pagos | Pendiente |
| — | Exámenes de grado | Pendiente |
| — | Planillas de clase | Pendiente |

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
- La academia **BEKHO**.
- Un usuario **super administrador** (ver credenciales abajo).

### 5. Levantar el frontend

```bash
npm run dev
```

Y sirve la app con Laragon (dominio `bekho.test`) o con `php artisan serve`.

---

## Credenciales de desarrollo

El seeder crea un super administrador:

| Campo | Valor |
|-------|-------|
| Email | `admin@bekho.cl` |
| Contraseña | `cambiar-esto` |
| Rol | `super-admin` |
| `academia_id` | `null` (ve todas las academias) |

> ⚠️ **Cambia esta contraseña de inmediato** en cualquier entorno que no sea tu máquina
> local. Nunca despliegues con estas credenciales por defecto.

---

## Arquitectura

### Multi-tenant por diseño, una sola academia en operación

Casi toda tabla del dominio lleva una columna `academia_id`. **Esto no significa que
BEKHO sea un SaaS multi-cliente.** En la práctica se opera con **una sola academia**
(BEKHO); lo que en el mundo real son otras sucursales (por ejemplo **POWER**) se modelan
como **sedes internas** de la misma academia, no como academias separadas.

`academia_id` es una **costura para el futuro**: deja lista la separación por academia por
si algún día se necesita, sin construir hoy el aparato de un SaaS. No hay que activar nada
extra para operar con una academia.

### Aislamiento por academia (tenancy)

El aislamiento se apoya en tres piezas:

1. **`App\Support\Tenancy\Academia`** — contenedor estático de la academia activa durante
   la petición (`set()`, `id()`, `hayActiva()`, `olvidar()`).
2. **`App\Models\Concerns\PerteneceAcademia`** — trait que se agrega a los modelos con
   `academia_id`. Hace dos cosas:
   - Añade un **global scope** que filtra `where academia_id = <activa>` **solo si hay una
     academia activa**.
   - En `creating`, **autorellena** `academia_id` con la academia activa si el modelo no la
     trae.
   - Expone el scope `sinAcademia()` para saltarse el filtro cuando haga falta.
3. **`App\Http\Middleware\EstableceAcademiaActual`** — corre después de autenticar
   (registrado en el grupo `web` con `append`). Fija la academia activa desde
   `user->academia_id`. **Un `super-admin` no fija ninguna academia y ve todas.**

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

### Roles y permisos

Roles (spatie, sin teams):

| Rol | Permisos |
|-----|----------|
| `super-admin` | Todos |
| `maestro` | Todos |
| `instructor` | `tomar asistencia`, `gestionar planillas`, `ver formacion` |
| `alumno` | `ver formacion` |
| `apoderado` | Ninguno |

Permisos definidos: `gestionar alumnos`, `tomar asistencia`, `registrar pagos`,
`gestionar examenes`, `gestionar planillas`, `gestionar formacion`, `ver formacion`.

---

## Módulos

### Hoy (implementado)

**Núcleo (Fase 1)**
- Academias, sedes (con pivote instructor↔sede), catálogo de rangos.
- Usuarios extendidos: `academia_id`, `rango_id`, `supervisor_id` (jerarquía), `telefono`,
  `activo`.
- Tenancy por academia y roles/permisos.

**Formación / LMS (Fase 2)**
- Estructura **niveles → contenidos → progreso por usuario**.
- Un **contenido** vive en **un solo nivel** (no se comparte entre niveles).
- El **progreso** por usuario guarda **solo el estado** (avance del alumno), no una copia
  del contenido.
- Los **videos se alojan externamente** (no se sirven desde la app); el LMS guarda la
  referencia, no el archivo.

### Después (pendiente)

- **Gestión**: alumnos, asistencia, pagos.
- **Exámenes** de grado.
- **Planillas** de clase.

---

## Seguridad

Resumen del estado real de las funciones que trae el starter kit (auditadas sobre el
código). Varias vienen "andamiadas" pero no todas están activas.

| Función | Estado hoy | Nota |
|---------|-----------|------|
| **Login rate limiting** | **Activo** | 5/min por email+IP; 2FA 5/min; passkeys 10/min |
| **2FA (TOTP)** | Disponible, **opcional** | Columnas + UI listas; nadie obligado todavía |
| **Passkeys (WebAuthn)** | Disponible, opcional | Alternativa sin contraseña |
| **Recuperación de contraseña** | Activo | Requiere `MAIL_*` configurado en producción |
| **Confirmación de contraseña** | Activo | Protege la pantalla de seguridad (timeout 3 h) |
| **Verificación de email** | **Andamiada, inactiva** | Falta `implements MustVerifyEmail` en `User`; el middleware `verified` no bloquea |
| **Política de contraseñas** | Fuerte **solo en producción** | 12 + símbolos + chequeo HIBP; en local, mínimo 8 |
| **Cookie de sesión segura** | **No forzada** | Poner `SESSION_SECURE_COOKIE=true` + HTTPS en producción |
| **Auto-registro público** | **Abierto** | `/register` acepta a cualquiera |

### Pendiente de decidir (antes de producción)

- **2FA obligatoria por rol** para cuentas privilegiadas (`super-admin`, `maestro`), dejando
  el resto opcional. Hay **datos de menores**, así que proteger esas cuentas sí importa;
  pero obligar 2FA a un alumno de 12 años es contraproducente.
- **Cerrar o restringir el auto-registro**: en una escuela las cuentas las crea el
  maestro/admin.
- **Forzar la cookie segura** y HTTPS.
- **Verificación de email**: activarla cuando el flujo de altas lo justifique (no urgente si
  las cuentas las controla la escuela).

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
