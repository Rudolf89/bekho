<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $planilla->nombre }}</title>
    <style>
        /* Hoja pensada para papel: sin dependencias ni assets, para que imprima
           igual desde cualquier equipo de la sede. */
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #18181b; margin: 0; padding: 16px; }
        .barra { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 16px; }
        .barra button { font: inherit; padding: 6px 14px; border: 1px solid #18181b; background: #18181b; color: #fff; cursor: pointer; border-radius: 6px; }
        header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; border-bottom: 2px solid #18181b; padding-bottom: 8px; margin-bottom: 4px; }
        h1 { font-size: 18px; margin: 0; }
        .meta { font-size: 11px; color: #52525b; }
        .casillas { display: flex; gap: 18px; font-size: 11px; margin: 10px 0 14px; }
        .casillas span { flex: 1; border-bottom: 1px solid #a1a1aa; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #52525b; padding: 0 6px; font-size: 11px; text-align: left; }
        th { background: #f4f4f5; height: 26px; font-weight: 700; }
        td { height: 26px; }
        .num { width: 28px; text-align: center; color: #71717a; }
        footer { margin-top: 10px; font-size: 10px; color: #71717a; display: flex; justify-content: space-between; }
        @media print { .barra { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="barra"><button onclick="window.print()">Imprimir</button></div>

    <header>
        <div>
            <h1>{{ $planilla->nombre }}</h1>
            @if ($planilla->descripcion)
                <div class="meta">{{ $planilla->descripcion }}</div>
            @endif
        </div>
        <div class="meta">
            {{ $planilla->uso }} · {{ $planilla->estado->etiqueta() }} {{ $planilla->etiquetaVersion() }}
        </div>
    </header>

    {{-- Encabezado que se llena a mano: el sistema no sabe de qué clase es. --}}
    <div class="casillas">
        <span>Sede:</span>
        <span>Clase / categoría:</span>
        <span>Instructor:</span>
        <span>Fecha:</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                @foreach ($planilla->columnas as $columna)
                    <th>{{ $columna->titulo }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for ($fila = 1; $fila <= $planilla->filas; $fila++)
                <tr>
                    <td class="num">{{ $fila }}</td>
                    @foreach ($planilla->columnas as $columna)
                        <td></td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>

    <footer>
        <span>BEKHO · Taekwondo ATA</span>
        @unless ($planilla->verificado)
            <span>Planilla sin verificar por la federación</span>
        @endunless
    </footer>
</body>
</html>
