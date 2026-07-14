<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'BEKHO') : config('app.name', 'BEKHO') }}
</title>

@if (file_exists(public_path('img/bekho-logo.png')))
    <link rel="icon" type="image/png" href="{{ asset('img/bekho-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/bekho-logo.png') }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
