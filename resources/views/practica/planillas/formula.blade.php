<x-hoja-impresion titulo="Fórmula y Armas">
    <header>
        <h1>Sección 1. Fórmula · Armas</h1>
        <div class="grupo-marcas">
            <strong style="margin-right:8px">Competidores</strong>
            <span class="marcar"><i></i> Cinturones de Color</span>
            <span class="marcar"><i></i> Cinturones Negros</span>
            <strong style="margin:0 8px">Género</strong>
            <span class="marcar"><i></i> Masculino</span>
            <span class="marcar"><i></i> Femenino</span>
        </div>
    </header>

    <div class="casillas">
        <span>Hora inicio:</span>
        <span>Hora término:</span>
        <span>Fecha:</span>
        <span>N.º de pista:</span>
    </div>

    {{-- Las dos pruebas van lado a lado, como en la planilla oficial. --}}
    <div style="display:flex; gap:10px; align-items:flex-start">
        @foreach ($pruebas as $prueba)
            <div style="flex:1; min-width:0">
                <table>
                    <thead>
                        <tr>
                            <th colspan="{{ $prueba->modalidad === 'formas' ? 4 : 2 }}" style="text-align:center">{{ $prueba->nombre }}</th>
                            <th colspan="{{ $prueba->criterios->count() }}" style="text-align:center">Puntajes</th>
                            <th rowspan="3" style="width:8%; vertical-align:middle; text-align:center">Total</th>
                        </tr>
                        <tr>
                            <th colspan="{{ $prueba->modalidad === 'formas' ? 4 : 2 }}"></th>
                            @foreach ($prueba->criterios as $criterio)
                                <th style="text-align:center">{{ $criterio->papel_juez->etiqueta() }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="num">#</th>
                            <th>Nombre de los competidores</th>
                            @if ($prueba->modalidad === 'formas')
                                <th style="width:8%">Edad</th>
                                <th style="width:12%">País</th>
                            @endif
                            @foreach ($prueba->criterios as $criterio)
                                <th style="font-weight:400">{{ $criterio->nombre }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($fila = 1; $fila <= $competidores; $fila++)
                            <tr>
                                <td class="num">{{ $fila }}.-</td>
                                <td></td>
                                @if ($prueba->modalidad === 'formas')
                                    <td></td>
                                    <td></td>
                                @endif
                                @foreach ($prueba->criterios as $criterio)
                                    <td></td>
                                @endforeach
                                <td></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <h2>Resultados {{ $prueba->nombre }}</h2>
                <table>
                    <tbody>
                        @foreach (['1er lugar', '2do lugar', '3er lugar'] as $lugar)
                            <tr>
                                <th style="width:22%">{{ $lugar }}</th>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <h2>Jueces de la pista</h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width:22%"></th>
                            <th style="width:32%">Nivel · País</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['Juez central', 'Juez A', 'Juez B', 'Planillero'] as $papel)
                            <tr>
                                <th>{{ $papel }}</th>
                                <td></td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <h2>Grupo de edades</h2>
    <div class="grupo-marcas">
        @foreach ($gruposEdad as $grupo)
            <span class="marcar"><i></i> {{ $grupo->nombre }}</span>
        @endforeach
    </div>

    <h2>Cinturones · Categorías</h2>
    <div class="grupo-marcas">
        @foreach ($categorias as $categoria)
            <span class="marcar"><i></i> {{ $categoria->nombre }}</span>
        @endforeach
    </div>

    {{-- Nota al pie de la planilla oficial. --}}
    <p style="margin-top:6px; font-size:10px; color:#52525b">
        Nota: la edad es solo para cinturones negros y el país es para el Panamericano.
    </p>
</x-hoja-impresion>
