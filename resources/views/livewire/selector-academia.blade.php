<div>
    <flux:select wire:model.live="academiaActiva" size="sm" aria-label="Academia activa">
        @foreach ($academias as $academia)
            <flux:select.option value="{{ $academia->id }}">{{ $academia->nombre }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
