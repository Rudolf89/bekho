# Línea de supervisión ↔ Academias — checklist de validación

> ⚠️ **DATOS NO CONFIRMADOS POR LA FEDERACIÓN.** Todo lo de abajo son inferencias
> sobre fragmentos públicos de buscador (los sitios principales bloquean el acceso
> automatizado). Sirve como punto de partida para validar a mano contra el registro
> oficial de la federación, **no** como la verdad. Se sembró tal cual
> (`AcademiasBekhoSeeder` + `database/data/academias_bekho.json`) para poder
> revisarlo dentro del sistema, con `confirmado: false` en cada entrada.

De las **19 opciones del padre** (campos de la línea de supervisión), **8** tienen
alguna asociación tras cruzar web + Google Maps. **5** con base suficiente están
**enlazadas en el seed**; **3** son solo pistas por verificar. Faltan **11**.

## Confianza alta — enlazadas en el seed (academia_id fijado)

| Campo | Supervisor | Academia | Sitio | Base |
|---|---|---|---|---|
| 108 | CHIEF MASTER SOTOMAYOR | Academia Oriente | ataoriente.cl | Claudio Sotomayor Di-Biaggio, fundador de BEKHO Chile (1989) y presidente de BEKHO Martial Arts; el dominio es su academia. |
| 116 | MASTER CRISTIAN NOGUES | ATA BEKHO Pride | atapride.cl | El sitio lo nombra explícitamente como director. |
| 134 | SENIOR MASTER VICTOR RODRIGUEZ | BEKHO Power Academy | atapower.cl | Staff (Núñez, Donoso, Pinto, Suazo) + fotos de fichas subidas por Isis Elizondo, Kevin Suazo (Quilicura) y Maximiliano Carreño (Pudahuel/Las Torres), todos del 134. |
| 111 | SENIOR MASTER PABLO MARTINEZ | ATA BEKHO Martínez | ata-martinez.cl | Doble señal: la sede de Maipú (Luis Gandarillas 395) usa ata-martinez.cl, y reseñas agradecen a Félix Pérez y Fernanda (Jeldres), ambos del 111. |
| 115 | MASTER JOSE MIGUEL RAIGAN | ATA BEKHO Raigán | ATA Raigans | Ciudad del Niño (San Miguel): foto de la ficha subida por 'ATA Raigans Ciudad del Niño' + Instagram @bekho.ciudaddelnino. |

**Sedes sembradas:**
- **Oriente:** Omnium (Apoquindo 4900, Las Condes — ver anomalía abajo).
- **Pride:** Maipú, Nos, Calera de Tango, Peñaflor.
- **Power:** Quilicura, Valle Grande, Pudahuel, Valle del Sol.
- **Martínez:** Maipú (Luis Gandarillas 395).
- **Raigán:** Ciudad del Niño (Séptima Avenida 1261, San Miguel).

**A estos 5 campos se les fijó `academia_id`** en su supervisor + su subárbol
directo (por `supervisor_id`, no por la lista del campo: así quien figura en dos
campos queda en la academia de su supervisor canónico). El resto de la línea sigue
a nivel federación (`academia_id = null`).

## Confianza media/baja — pistas NO sembradas (verificar)

Salieron de fotos/reseñas de Google Maps (contenido de usuarios, no oficial): que
alguien suba una foto no prueba que sea el instructor a cargo. **No se enlazaron.**

| Campo | Supervisor | Academia probable | Base (débil) |
|---|---|---|---|
| 112 | SENIOR MASTER VILLANUEVA | ATA Bekho Larraín (La Reina), atabekhoforce.cl | Una foto de la ficha la subió la cuenta 'Camila Müller', nombre del campo 112. |
| 122 | SR. ALBERTO CARICEO | ATA Bekho La Florida | Foto de la ficha subida por la cuenta 'Ata Cariceo'. |

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
| BEKHO Academias.cl (red) | Santiago Centro (Zenteno 1395); San Miguel (Gran Avenida 5116; San Ignacio 4815); La Florida (Walker Martínez 2295) | academias.cl |
| Casa Central BEKHO | Av. Alcalde Fernando Castillo Velasco 7059, Local H | bekhomartialarts.com |

## Redes y sedes detectadas en Maps aún NO sembradas

Google Maps devolvió ~20 sedes y cinco redes con dominio propio que faltan por
mapear. No se sembraron todavía (sin academia/instructor claro, o son solo sedes
sueltas que no pueden colgar de ninguna academia):

- **Redes con dominio propio:** `atabekhoforce.cl` (Force), `atastrike.cl` (Strike,
  sede Dublé Almeyda / Ñuñoa), Kick Revolution (sede Covadonga / San Bernardo).
- **Sedes sueltas sin operador identificado:** ATA Bekho Av. Las Rejas (Estación
  Central), ATA Plaza Brasil, ATA Ciudad de los Valles (Pudahuel), ATA Bekho
  Gimnasio Lessen (Vitacura, probablemente club, no academia), Academia Taekwondo
  ATA Batuco (Viña del Mar).

## Anomalías que complican el modelo (y por qué el modelo aguanta)

- **Omnium y Academia Oriente comparten dirección exacta** (Apoquindo 4900, Las
  Condes) pero Omnium apunta a atapower.cl y Oriente a ataoriente.cl, con teléfonos
  distintos. Dos fichas, mismo local, dos redes. **No rompe el modelo:** `Sede`
  tiene `academia_id` y la dirección es solo un `string` (no es clave única), así
  que dos academias pueden tener sedes en la misma dirección sin conflicto. Queda
  por confirmar de quién es Omnium (lo dejé como sede de Oriente).
- **Teléfonos repetidos:** Zenteno y Gran Avenida comparten número (red
  academias.cl); Plaza Brasil y Ciudad de los Valles comparten otro. El teléfono
  identifica al operador, no a la sede — por eso el modelo no usa el teléfono como
  identidad; la identidad es `academia_id`.
- **Vespucio Maipú:** una foto la subió Franco Poblete (campo 108, Sotomayor), lo
  que sugiere que es de Oriente y no de Pride.

## Pistas sueltas para la validación

- **Di-Biaggio:** `SR. GASTON DI-BIAGGIO` figura en el campo 123 (bajo Manuel
  Schibar). Di-Biaggio es el segundo apellido de Sotomayor y el nombre del club
  fundacional; probablemente familiar, sin fuente que lo confirme.

## Camino corto para cerrar los 11 restantes

Buscar cada red por su dominio propio (ata-martinez.cl, atabekhoforce.cl,
atastrike.cl, atapride.cl, atapower.cl, ataoriente.cl, ataestoril.cl,
escueladeartesmarciales.cl): si cada dominio publica su staff, se completa buena
parte del mapa. Alternativa definitiva: el registro oficial de la federación. En
cuanto lleguen los datos reales, actualizar `database/data/academias_bekho.json`
(poner `confirmado: true`, corregir campos) y volver a sembrar.
