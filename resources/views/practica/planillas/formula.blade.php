<x-hoja-impresion :titulo="'Fórmula y Armas — '.$prueba->nombre">
    <header>
        <div>
            <h1>Fórmula y Armas</h1>
            <div class="meta">{{ $prueba->nombre }}</div>
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

    <h2>Competidores</h2>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th style="width:22%">Competidor</th>
                <th style="width:6%">Edad</th>
                <th style="width:14%">País</th>
                @foreach ($prueba->criterios as $criterio)
                    <th>{{ $criterio->papel_juez->etiqueta() }}<br><span style="font-weight:400">{{ $criterio->nombre }}</span></th>
                @endforeach
                <th style="width:9%">Total</th>
            </tr>
        </thead>
        <tbody>
            @for ($fila = 1; $fila <= $competidores; $fila++)
                <tr>
                    <td class="num">{{ $fila }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @foreach ($prueba->criterios as $criterio)
                        <td></td>
                    @endforeach
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <h2>Resultados</h2>
    <table>
        <tbody>
            @foreach (['1.º lugar', '2.º lugar', '3.º lugar'] as $lugar)
                <tr>
                    <th style="width:12%">{{ $lugar }}</th>
                    <td></td>
                </tr>
            @endforeach
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
