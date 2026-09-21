<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Gestión · Programa</flux:text>
        <flux:heading size="xl" class="mt-1">Hojas para practicar</flux:heading>
        <flux:text class="mt-1">
            Las tres secciones de la planilla de competencia oficial, en blanco, para practicar
            el llenado a mano. Se generan desde los catálogos de la federación, así que las
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
                        {{ $pruebas->pluck('nombre')->join(' y ') }} lado a lado: 16 competidores con los
                        criterios de cada juez, resultados, jueces de la pista y las casillas de edad y categoría.
                    </flux:text>
                </div>
                <flux:button size="sm" variant="filled" icon="printer"
                             :href="route('practica.planillas.formula')" target="_blank">
                    Imprimir
                </flux:button>
            </li>

            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <flux:icon.document-text class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                <div class="min-w-0 flex-1 basis-64">
                    <flux:heading>Sparring</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        Tabla de libres, llave de 16 con puntos y advertencias por ronda, finalistas,
                        resultados, registro de firmas y observaciones.
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
                        Conteo por pista de medallas de 1.er, 2.º y 3.er lugar y de participación.
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
