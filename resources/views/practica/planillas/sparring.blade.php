<x-hoja-impresion titulo="Sparring">
    <header>
        <div>
            <h1>Sparring</h1>
            <div class="meta">Llave de {{ $competidores }} competidores</div>
        </div>
        <div class="grupo-marcas">
            <span class="marcar"><i></i> Competidores color</span>
            <span class="marcar"><i></i> Competidores negros</span>
        </div>
    </header>

    <div class="casillas">
        <span>Género:</span>
        <span>Horario:</span>
        <span>Fecha:</span>
        <span>N.º de pista:</span>
    </div>

    {{-- La tabla de libres va en el encabezado, como en la planilla oficial. --}}
    <h2>Tabla de libres</h2>
    <table>
        <thead>
            <tr>
                <th style="width:14%">Competidores</th>
                @foreach ($tablaLibres as $fila)
                    <th style="text-align:center">{{ $fila->competidores }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Libres</th>
                @foreach ($tablaLibres as $fila)
                    <td style="text-align:center">{{ $fila->libres }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <h2>Grupo de edad</h2>
    <div class="grupo-marcas">
        @foreach ($gruposEdad as $grupo)
            <span class="marcar"><i></i> {{ $grupo->nombre }}</span>
        @endforeach
    </div>

    <h2>Categoría</h2>
    <div class="grupo-marcas">
        @foreach ($categorias as $categoria)
            <span class="marcar"><i></i> {{ $categoria->nombre }}</span>
        @endforeach
    </div>

    {{-- Llave: puntos (P) y advertencias (A) por ronda para cada competidor. --}}
    <h2>Llave</h2>
    <table>
        <thead>
            <tr>
                <th class="num" rowspan="2">#</th>
                <th style="width:26%" rowspan="2">Competidor</th>
                @foreach ($rondas as $ronda)
                    <th colspan="2" style="text-align:center">{{ $ronda }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($rondas as $ronda)
                    <th style="text-align:center">P</th>
                    <th style="text-align:center">A</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for ($fila = 1; $fila <= $competidores; $fila++)
                <tr>
                    <td class="num">{{ $fila }}</td>
                    <td></td>
                    @foreach ($rondas as $ronda)
                        <td></td>
                        <td></td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>

    <h2>Finalistas por 3.º y 4.º lugar</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40%">Competidor</th>
                <th style="text-align:center; width:10%">P</th>
                <th style="text-align:center; width:10%">A</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            <tr><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <h2>Resultados</h2>
    <table>
        <tbody>
            @foreach (['1.º lugar', '2.º lugar', '3.º lugar', '4.º lugar'] as $lugar)
                <tr>
                    <th style="width:12%">{{ $lugar }}</th>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Observaciones</h2>
    <table>
        <tbody>
            <tr><td style="height:34px"></td></tr>
            <tr><td style="height:34px"></td></tr>
        </tbody>
    </table>

    <h2>Jueces de la pista</h2>
    <table>
        <tbody>
            @foreach (['Juez central', 'Juez A', 'Juez B', 'Planillero'] as $papel)
                <tr>
                    <th style="width:12%">{{ $papel }}</th>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-hoja-impresion>
