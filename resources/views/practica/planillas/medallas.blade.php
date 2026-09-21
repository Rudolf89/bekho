<x-hoja-impresion titulo="Recuento de medallas" orientacion="portrait">
    <header>
        <div>
            <h1>Recuento de medallas</h1>
            <div class="meta">Una línea por grupo de edad y categoría</div>
        </div>
    </header>

    <div class="casillas">
        <span>Torneo:</span>
        <span>Fecha:</span>
        <span>Planillero:</span>
    </div>

    {{--
        La planilla oficial no detalla las columnas de esta hoja: se arma con los
        mismos catálogos que las otras dos (grupo de edad × categoría × prueba) y
        los lugares 1.º a 3.º que sí están definidos. Revisar con la federación.
    --}}
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th style="width:20%">Grupo de edad</th>
                <th style="width:20%">Categoría</th>
                <th style="width:18%">Prueba</th>
                <th>1.º lugar</th>
                <th>2.º lugar</th>
                <th>3.º lugar</th>
            </tr>
        </thead>
        <tbody>
            @for ($fila = 1; $fila <= $filas; $fila++)
                <tr>
                    <td class="num">{{ $fila }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <h2>Referencia · grupos de edad</h2>
    <div class="grupo-marcas">
        @foreach ($gruposEdad as $grupo)
            <span class="marcar" style="border:0">{{ $grupo->nombre }}@if ($grupo->edad_desde) ({{ $grupo->edad_desde }}–{{ $grupo->edad_hasta }})@endif</span>
        @endforeach
    </div>

    <h2>Referencia · categorías</h2>
    <div class="grupo-marcas">
        @foreach ($categorias as $categoria)
            <span class="marcar" style="border:0">{{ $categoria->nombre }}</span>
        @endforeach
    </div>
</x-hoja-impresion>
