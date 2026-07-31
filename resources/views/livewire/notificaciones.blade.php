<div class="mx-auto w-full max-w-3xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Notificaciones</flux:heading>
            <flux:text class="mt-1">
                @if ($noLeidas > 0)
                    Tienes {{ $noLeidas }} {{ $noLeidas === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }}.
                @else
                    Estás al día.
                @endif
            </flux:text>
        </div>
        @if ($noLeidas > 0)
            <flux:button size="sm" variant="filled" wire:click="marcarTodasLeidas" icon="check">Marcar todas como leídas</flux:button>
        @endif
    </div>

    <div class="space-y-2">
        @forelse ($notificaciones as $n)
            @php($d = $n->data)
            @php($leida = $n->read_at !== null)
            <div wire:key="notif-{{ $n->id }}"
                 class="flex items-start gap-3 rounded-xl border p-4 transition-colors
                    {{ $leida
                        ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800'
                        : 'border-red-200 bg-red-50/50 dark:border-red-500/30 dark:bg-red-950/20' }}">
                <span class="mt-1 inline-block h-2.5 w-2.5 shrink-0 rounded-full {{ $leida ? 'bg-zinc-300 dark:bg-zinc-600' : 'bg-red-500' }}"></span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:text class="font-semibold">{{ $d['titulo'] ?? 'Notificación' }}</flux:text>
                        @if (isset($d['estado_etiqueta']))
                            <flux:badge size="sm" :color="$d['color'] ?? 'zinc'">{{ $d['estado_etiqueta'] }}</flux:badge>
                        @endif
                        @isset($d['porcentaje'])
                            <flux:badge size="sm" color="zinc">{{ $d['porcentaje'] }}%</flux:badge>
                        @endisset
                    </div>

                    @isset($d['mensaje'])
                        <flux:text size="sm" class="mt-0.5 block text-zinc-600 dark:text-zinc-300">{{ $d['mensaje'] }}</flux:text>
                    @endisset

                    @if (! empty($d['justificacion']))
                        <flux:text size="sm" class="mt-1 block italic text-zinc-500">Nota del examinador: {{ $d['justificacion'] }}</flux:text>
                    @endif

                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <flux:text size="xs" class="text-zinc-400">{{ $n->created_at->diffForHumans() }}</flux:text>
                        @if (isset($d['intento_id']) && \Illuminate\Support\Facades\Route::has('cuestionarios.mis-intentos'))
                            <flux:link :href="route('cuestionarios.mis-intentos')" wire:navigate class="text-xs">Ver mis intentos</flux:link>
                        @endif
                        @unless ($leida)
                            <button type="button" wire:click="marcarLeida('{{ $n->id }}')" class="text-xs text-zinc-500 hover:underline">Marcar como leída</button>
                        @endunless
                        <button type="button" wire:click="eliminar('{{ $n->id }}')" class="text-xs text-zinc-400 hover:text-red-600 hover:underline">Eliminar</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <flux:text>No tienes notificaciones.</flux:text>
            </div>
        @endforelse
    </div>
</div>
