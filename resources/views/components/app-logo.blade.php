@props([
    'sidebar' => false,
    'href' => null,
])

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    {{-- Barras de la marca BEKHO (rojo + negro) --}}
    <span class="flex h-8 items-center gap-[3px]">
        <span class="h-8 w-[5px] rounded-full bg-[#b01e28]"></span>
        <span class="h-8 w-[5px] rounded-full bg-zinc-900 dark:bg-white"></span>
    </span>
    <span class="grid leading-none">
        <span class="text-lg font-extrabold tracking-tight text-zinc-900 dark:text-white">BEKHO</span>
        <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Taekwondo · ATA</span>
    </span>
</a>
