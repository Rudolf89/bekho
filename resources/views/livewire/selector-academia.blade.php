<div>
    <flux:select wire:model.live="academiaActiva" size="sm" aria-label="Academia activa" placeholder="Todas las academias">
        <flux:select.option value="">Todas las academias</flux:select.option>
        @foreach ($academias as $academia)
            <flux:select.option value="{{ $academia->id }}">{{ $academia->nombre }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
