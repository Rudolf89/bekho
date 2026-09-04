# Línea de supervisión ↔ Academias — checklist de validación

> ⚠️ **DATOS NO CONFIRMADOS POR LA FEDERACIÓN.** Todo lo de abajo son inferencias
> sobre fragmentos públicos de buscador (los sitios principales bloquean el acceso
> automatizado). Sirve como punto de partida para validar a mano contra el registro
> oficial de la federación, **no** como la verdad. Se sembró tal cual
> (`AcademiasBekhoSeeder` + `database/data/academias_bekho.json`) para poder
> revisarlo dentro del sistema, con `confirmado: false` en cada entrada.

De las **19 opciones del padre** (campos de la línea de supervisión), solo **3** se
pudieron asociar a una academia con base suficiente. Faltan **16**.

## Confianza alta — enlazadas en el seed

| Campo | Supervisor | Academia | Sitio | Base |
|---|---|---|---|---|
| 108 | CHIEF MASTER SOTOMAYOR | Academia Oriente | ataoriente.cl | Claudio Sotomayor Di-Biaggio, fundador de BEKHO Chile (1989) y presidente de BEKHO Martial Arts; el dominio es su academia. |
| 116 | MASTER CRISTIAN NOGUES | ATA BEKHO Pride | atapride.cl | El sitio lo nombra explícitamente como director. |
| 134 | SENIOR MASTER VICTOR RODRIGUEZ | BEKHO Power Academy | atapower.cl | Cuatro nombres del campo 134 figuran en su staff (ver abajo). |

**Sedes sembradas:**
- **Oriente:** Omnium (central; el listado actual no se obtuvo).
- **Pride:** Maipú, Nos, Calera de Tango, Peñaflor.
- **Power:** Quilicura, Valle Grande, Pudahuel, Valle del Sol.

**Solo a estos 3 campos se les fijó `academia_id`** en sus usuarios (supervisor +
miembros). El resto de la línea sigue a nivel federación (`academia_id = null`).

## Confianza media — cargos internos de Power (campo 134)

Todos aparecen en el campo 134; no se modeló su cargo, solo su pertenencia.

| Persona | Cargo según el sitio |
|---|---|
| Cristian Núñez | IV Dan, directorio; instructor jefe de Quilicura y Valle Grande |
| Mauricio Donoso | III Dan, directorio; instructor jefe de Pudahuel y Valle del Sol |
| Mauricio Pinto | IV Dan, directorio y staff; Departamento de Reportes en Casa Central |
| Kevin Suazo | III Dan, instructor certificado |

**Inconsistencia abierta:** el campo 134 lo encabeza `SENIOR MASTER VICTOR
RODRIGUEZ`, pero en atapower.cl no aparece ningún Rodríguez en el staff. Puede que
el sitio esté desactualizado, que Rodríguez sea la autoridad de rango sin figurar
en el staff operativo, o que la lista 134 no corresponda a Power. **Validar.**

## Confianza baja — academias sin instructor identificado (sin campo)

Se sembraron como academias con sus sedes, pero **sin enlace a ningún campo**
(no se identificó instructor).

| Academia | Ubicación | Sitio / referencia |
|---|---|---|
| ATA BEKHO IV Región | La Serena | escueladeartesmarciales.cl |
| ATA BEKHO Estoril | Estoril 761, Las Condes | ataestoril.cl |
| BEKHO Academias.cl (red) | Santiago Centro (Zenteno 1395); San Miguel (Gran Avenida 5116; San Ignacio 4815; Séptima Avenida 1261); La Florida (Walker Martínez 2295) | academias.cl |
| Casa Central BEKHO | Av. Alcalde Fernando Castillo Velasco 7059, Local H | bekhomartialarts.com |

## Pistas sueltas para la validación

- **Ciudad del Niño:** la sede de academias.cl en Séptima Avenida usa el Instagram
  `@bekho.ciudaddelnino`, distinto al `@academiascl` de las demás → parece un nombre
  de academia propio dentro de esa red.
- **Di-Biaggio:** `SR. GASTON DI-BIAGGIO` figura en el campo 123 (bajo Manuel
  Schibar). Di-Biaggio es el segundo apellido de Sotomayor y el nombre del club
  fundacional; probablemente familiar, sin fuente que lo confirme.

## Camino corto para cerrar los 16 restantes

El mapa de academias del sitio oficial revisado a mano, o directamente el registro
de la federación. En cuanto lleguen los datos reales, actualizar
`database/data/academias_bekho.json` (poner `confirmado: true`, corregir campos) y
volver a sembrar.
