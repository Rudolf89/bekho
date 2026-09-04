@props(['placeholder' => 'Buscar…'])

{{-- Buscador de tabla: se enlaza a la propiedad `buscar` del trait ConTabla. --}}
<flux:input
    wire:model.live.debounce.300ms="buscar"
    :placeholder="$placeholder"
    icon="magnifying-glass"
    size="sm"
    {{ $attributes->merge(['class' => 'w-full sm:max-w-xs']) }}
/>
