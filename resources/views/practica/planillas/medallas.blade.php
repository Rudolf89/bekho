<x-hoja-impresion titulo="Recuento de medallas" orientacion="portrait">
    <header>
        <h1>Sección 3. Recuento de medallas</h1>
        <div class="meta">Requerimiento de medallas</div>
    </header>

    <div class="casillas">
        <span>Fecha:</span>
        <span>Planillero:</span>
    </div>

    {{-- Tal como la planilla oficial: N.º de pista y el conteo por lugar. --}}
    <table style="margin-top:8px">
        <thead>
            <tr>
                <th style="width:22%">N.º pista</th>
                <th style="width:14%"></th>
                <th style="text-align:center">1er lugar</th>
                <th style="text-align:center">2do lugar</th>
                <th style="text-align:center">3er lugar</th>
                <th style="text-align:center">Participación</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>N.º de medallas</th>
                <td style="height:26px"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</x-hoja-impresion>
