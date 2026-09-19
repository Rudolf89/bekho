# Riesgo abierto: datos personales inferidos en el historial de git

**Estado:** ABIERTO — requiere decisión del dueño del repositorio.
**Naturaleza:** privacidad / datos personales, no técnico.

## Qué pasó

Durante la Fase 0 se sembraron una "línea de supervisión" y un listado de
academias/afiliaciones con **nombres reales de instructores y afiliaciones
inferidas de búsquedas web**, no confirmadas por la federación. Los archivos
involucrados fueron:

- `docs/linea-supervision-academias.md`
- `database/data/linea_supervision.json`
- `database/data/academias_bekho.json`

Esos archivos **ya no están en el árbol de trabajo**: se eliminaron en el commit
`e3105dc` ("Rediseño Fase 0: federación raíz + limpieza de datos inferidos").

## Por qué sigue siendo un riesgo

El repositorio es **público** y el contenido **permanece en el historial de git**.
Los commits que lo introdujeron y modificaron siguen accesibles:

- `f0bcc5f`, `fe7e257`, `1446a23`, `2097b18`, `45ec89d`

Además, **algunos mensajes de commit contienen nombres reales**, por lo que ni
siquiera basta con purgar el contenido de los archivos: también habría que sanear
los mensajes.

Borrar los archivos en un commit nuevo **no elimina** la información del historial.

## Remediación posible (decisión del dueño)

1. **Poner el repositorio en privado** en GitHub (corta la exposición de inmediato).
2. **Reescribir el historial** para eliminar archivos y sanear mensajes de commit
   (`git filter-repo` o BFG). Es **destructivo**: cambia todos los SHAs, exige
   `force-push` y que todos vuelvan a clonar. Debe cubrir **todas las ramas**,
   incluida la base ya mergeada.
3. **Rotar/avisar** si alguno de esos datos se considera sensible para las personas
   nombradas.

## Decisión tomada por ahora

Se optó por **documentar el riesgo** (este archivo) y dejar la remediación efectiva
para el dueño del repositorio. Ninguna herramienta automática reescribió el
historial en el marco de este rediseño.
