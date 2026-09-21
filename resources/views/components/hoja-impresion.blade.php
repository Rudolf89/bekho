@props(['titulo', 'orientacion' => 'landscape'])

{{--
    Hoja en blanco para practicar el llenado a mano. Va SIN el layout de la app
    y sin assets: así imprime igual desde cualquier equipo de la sede.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style>
        @page { size: A4 {{ $orientacion }}; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #18181b; margin: 0; padding: 14px; font-size: 11px; }
        .barra { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .barra button { font: inherit; padding: 6px 14px; border: 1px solid #18181b; background: #18181b; color: #fff; cursor: pointer; border-radius: 6px; }
        header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; border-bottom: 2px solid #18181b; padding-bottom: 6px; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 12px 0 4px; text-transform: uppercase; letter-spacing: 0.06em; }
        .meta { font-size: 10px; color: #52525b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #52525b; padding: 1px 4px; text-align: left; }
        th { background: #f4f4f5; font-weight: 700; height: 22px; }
        td { height: 20px; }
        .num { width: 24px; text-align: center; color: #71717a; }
        .casillas { display: flex; flex-wrap: wrap; gap: 14px; margin: 8px 0; }
        .casillas span { flex: 1 1 140px; border-bottom: 1px solid #a1a1aa; padding-bottom: 2px; }
        /* Casilla para marcar (grupo de edad, categoría, color/negro). */
        .marcar { display: inline-flex; align-items: center; gap: 4px; margin: 0 10px 4px 0; white-space: nowrap; }
        .marcar i { display: inline-block; width: 11px; height: 11px; border: 1px solid #52525b; }
        .grupo-marcas { display: flex; flex-wrap: wrap; margin-top: 2px; }
        footer { margin-top: 8px; font-size: 9px; color: #71717a; display: flex; justify-content: space-between; }
        @media print { .barra { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="barra"><button onclick="window.print()">Imprimir</button></div>
    {{ $slot }}
    <footer>
        <span>BEKHO · Taekwondo ATA — hoja para practicar</span>
        <span>{{ \App\Support\Competencia\HojasPractica::FUENTE }}</span>
    </footer>
</body>
</html>
