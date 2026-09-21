<x-hoja-impresion titulo="Sparring">
    <header>
        <h1>Sección 2. Sparring</h1>
        <div class="grupo-marcas">
            <span class="marcar"><i></i> Cinturones de Color</span>
            <span class="marcar"><i></i> Cinturones Negros</span>
            <span class="marcar"><i></i> Masculino</span>
            <span class="marcar"><i></i> Femenino</span>
        </div>
    </header>

    <div class="casillas">
        <span>N.º de competidores:</span>
        <span>N.º de libres:</span>
        <span>Fecha:</span>
        <span>N.º de pista:</span>
    </div>

    {{-- Tabla de libres: la referencia que el planillero consulta al armar la llave. --}}
    <table style="margin-top:6px">
        <tbody>
            <tr>
                <th style="width:14%">N.º de competidores</th>
                @foreach ($tablaLibres as $fila)
                    <th style="text-align:center">{{ str_pad((string) $fila->competidores, 2, '0', STR_PAD_LEFT) }}</th>
                @endforeach
            </tr>
            <tr>
                <th>N.º de libres</th>
                @foreach ($tablaLibres as $fila)
                    <td style="text-align:center">{{ str_pad((string) $fila->libres, 2, '0', STR_PAD_LEFT) }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <h2>Llave</h2>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                @foreach ($rondas as $ronda)
                    <th style="text-align:center">{{ $ronda['nombre'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for ($fila = 1; $fila <= $competidores; $fila++)
                <tr>
                    <td class="num">{{ $fila }}.-</td>
                    @foreach ($rondas as $ronda)
                        {{-- Cada casilla de una ronda cubre las líneas que se enfrentan en ella. --}}
                        @if (($fila - 1) % $ronda['lineas'] === 0)
                            <td rowspan="{{ $ronda['lineas'] }}" style="vertical-align:top">
                                <span style="display:block; color:#71717a">Puntos</span>
                                <span style="display:block; color:#71717a; margin-top:14px">Advertencias</span>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>

    @foreach (['Finalistas por 1er y 2do lugar', 'Finalistas por 3er y 4to lugar'] as $final)
        <h2>{{ $final }}</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:44%">Competidor</th>
                    <th style="text-align:center; width:14%">Puntos</th>
                    <th style="text-align:center; width:14%">Advertencias</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                <tr><td></td><td></td><td></td><td></td></tr>
                <tr><td></td><td></td><td></td><td></td></tr>
            </tbody>
        </table>
    @endforeach

    <h2>Resultados</h2>
    <table>
        <tbody>
            @foreach (['1er lugar', '2do lugar', '3er lugar'] as $lugar)
                <tr>
                    <th style="width:12%">{{ $lugar }}</th>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Registro de firmas</h2>
    <table>
        <thead>
            <tr>
                <th style="width:16%"></th>
                <th>Nombre</th>
                <th style="width:30%">Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['Juez A', 'Juez central', 'Juez B', 'Planillero'] as $papel)
                <tr>
                    <th>{{ $papel }}</th>
                    <td style="height:28px"></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Observaciones</h2>
    <table>
        <tbody>
            <tr><td style="height:32px"></td></tr>
            <tr><td style="height:32px"></td></tr>
        </tbody>
    </table>
</x-hoja-impresion>
