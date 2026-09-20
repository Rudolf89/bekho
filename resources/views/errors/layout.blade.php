<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'BEKHO') }}</title>
    <style>
        *{ box-sizing: border-box; }
        body{
            margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
            background:#f5f2ec; color:#27272a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .tarjeta{ text-align:center; padding:2.5rem 1.5rem; max-width:32rem; }
        .codigo{ font-size:5rem; font-weight:800; line-height:1; color:#b01e28; letter-spacing:-.03em; }
        .titulo{ margin:.75rem 0 .5rem; font-size:1.5rem; font-weight:700; }
        .detalle{ margin:0 0 1.75rem; color:#52525b; font-size:1rem; line-height:1.5; }
        .boton{
            display:inline-block; padding:.65rem 1.4rem; border-radius:.6rem;
            background:#b01e28; color:#fff; text-decoration:none; font-weight:600; font-size:.95rem;
        }
        .boton:hover{ background:#8f1820; }
        @media (prefers-color-scheme: dark){
            body{ background:#27272a; color:#e4e4e7; }
            .detalle{ color:#a1a1aa; }
            .codigo{ color:#e2565f; }
        }
    </style>
</head>
<body>
    <div class="tarjeta">
        <div class="codigo">@yield('code')</div>
        <h1 class="titulo">@yield('titulo')</h1>
        <p class="detalle">@yield('detalle')</p>
        <a class="boton" href="{{ url('/') }}">Volver al inicio</a>
    </div>
</body>
</html>
