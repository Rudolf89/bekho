<div>
    <flux:select wire:model.live="grupoActivo" size="sm" aria-label="Grupo activo" placeholder="Todos los grupos">
        <flux:select.option value="">Todos los grupos</flux:select.option>
        @foreach ($grupos as $grupo)
            <flux:select.option value="{{ $grupo->id }}">{{ $grupo->nombre }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
