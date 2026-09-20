@props([
    'sidebar' => false,
    'href' => null,
])

@php
    // Los PNG de marca se sirven desde public/img. Se prefiere la insignia
    // completa (con contornos) porque se ve bien tanto en tema claro como
    // oscuro; el logo horizontal pierde su borde blanco sobre fondo claro.
    // Mientras no exista ningún PNG, se muestra un logotipo textual de respaldo.
    $hayCompleto = file_exists(public_path('img/bekho-logo.png'));
    $hayHorizontal = file_exists(public_path('img/bekho-horizontal.png'));
@endphp

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    @if ($hayCompleto)
        <img src="{{ asset('img/bekho-logo.png') }}" alt="{{ config('app.name', 'BEKHO') }}" class="h-11 w-auto">
    @elseif ($hayHorizontal)
        <img src="{{ asset('img/bekho-horizontal.png') }}" alt="{{ config('app.name', 'BEKHO') }}" class="h-8 w-auto">
    @else
        {{-- Respaldo textual mientras no se agreguen los PNG en public/img. --}}
        <span class="flex h-8 items-center gap-[3px]">
            <span class="h-8 w-[5px] rounded-full bg-[#b01e28]"></span>
            <span class="h-8 w-[5px] rounded-full bg-zinc-900 dark:bg-white"></span>
        </span>
        <span class="grid leading-none">
            <span class="text-lg font-extrabold tracking-tight text-zinc-900 dark:text-white">BEKHO</span>
            <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Taekwondo · ATA</span>
        </span>
    @endif
</a>
