<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Gestión · Programa</flux:text>
        <flux:heading size="xl" class="mt-1">Hojas para practicar</flux:heading>
        <flux:text class="mt-1">
            Las tres hojas de la planilla de competencia oficial, en blanco, para practicar el
            llenado a mano. Se generan desde los catálogos de la federación, así que las
            casillas coinciden con la planilla digital.
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <flux:icon.document-text class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                <div class="min-w-0 flex-1 basis-64">
                    <flux:heading>Fórmula y Armas</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        16 competidores con edad, país y los criterios de cada juez, resultados y jueces de la pista.
                    </flux:text>
                </div>
                {{-- Sin shrink-0: en móvil los dos botones deben poder bajar de línea. --}}
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($pruebas as $prueba)
                        <flux:button size="sm" variant="filled" icon="printer"
                                     :href="route('practica.planillas.formula', $prueba)" target="_blank">
                            {{ $prueba->nombre }}
                        </flux:button>
                    @endforeach
                </div>
            </li>

            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <flux:icon.document-text class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                <div class="min-w-0 flex-1 basis-64">
                    <flux:heading>Sparring</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        Tabla de libres, llave de 16 con puntos y advertencias por ronda, finalistas por 3.º y 4.º lugar.
                    </flux:text>
                </div>
                <flux:button size="sm" variant="filled" icon="printer"
                             :href="route('practica.planillas.sparring')" target="_blank">
                    Imprimir
                </flux:button>
            </li>

            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <flux:icon.document-text class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                <div class="min-w-0 flex-1 basis-64">
                    <flux:heading>Recuento de medallas</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        Lugares 1.º a 3.º por grupo de edad, categoría y prueba.
                        <span class="text-amber-600 dark:text-amber-500">Columnas por confirmar con la federación.</span>
                    </flux:text>
                </div>
                <flux:button size="sm" variant="filled" icon="printer"
                             :href="route('practica.planillas.medallas')" target="_blank">
                    Imprimir
                </flux:button>
            </li>
        </ul>
    </div>
</div>
