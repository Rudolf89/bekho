<?php

use Illuminate\Support\Facades\File;

/*
 * Guardia contra un bug real de Flux visto dos veces: las directivas Blade
 * (@if, @foreach, …) NO se compilan cuando viven DENTRO de la etiqueta de un
 * componente Flux (zona de atributos) ni dentro del contenido de un
 * <flux:select.option>. Flux captura ese tramo y la directiva se imprime
 * literal (p. ej. «100 h@if (...) desde años@endif», o «@if="@if" wire:confirm=…»).
 *
 * El patrón correcto es envolver el componente con la directiva POR FUERA, o
 * usar una expresión ({{ $cond ? 'a' : 'b' }}). Este test recorre todas las
 * vistas Blade y falla si reaparece el anti-patrón.
 */

/**
 * Devuelve los fragmentos que incurren en el anti-patrón dentro de $src.
 *
 * @return list<string>
 */
function violacionesDirectivasFlux(string $src): array
{
    $dir = 'if|elseif|else|endif|unless|endunless|foreach|endforeach|forelse|endforelse|for|endfor|switch|endswitch|isset|endisset|empty|php';
    $atributo = '(?:"[^"]*"|\'[^\']*\'|[^>"\'])*';
    $violaciones = [];

    // Regla A: directiva en la zona de atributos de una etiqueta Flux.
    if (preg_match_all('/<flux:[A-Za-z0-9.:_-]+('.$atributo.')>/s', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $tag) {
            if (preg_match('/@(?:'.$dir.')\b/', $tag[1])) {
                $violaciones[] = trim(preg_replace('/\s+/', ' ', $tag[0]));
            }
        }
    }

    // Regla B: directiva dentro del contenido de <flux:select.option>.
    if (preg_match_all('/<flux:select\.option\b'.$atributo.'>(.*?)<\/flux:select\.option>/s', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $opt) {
            if (preg_match('/@(?:'.$dir.')\b/', $opt[1])) {
                $violaciones[] = trim(preg_replace('/\s+/', ' ', $opt[0]));
            }
        }
    }

    return $violaciones;
}

test('ninguna vista Blade usa directivas dentro de etiquetas Flux o de flux:select.option', function () {
    $violaciones = [];

    foreach (File::allFiles(resource_path('views')) as $archivo) {
        if (! str_ends_with($archivo->getFilename(), '.blade.php')) {
            continue;
        }

        foreach (violacionesDirectivasFlux(File::get($archivo->getPathname())) as $frag) {
            $violaciones[] = $archivo->getFilename().' → '.$frag;
        }
    }

    expect($violaciones)->toBe([]);
});

test('el detector reconoce el anti-patrón y no marca el uso correcto', function () {
    // Anti-patrón: directiva en atributos y en el contenido de select.option.
    expect(violacionesDirectivasFlux('<flux:button wire:click="x" @if ($a) wire:confirm="y" @endif />'))->not->toBe([])
        ->and(violacionesDirectivasFlux('<flux:select.option value="1">A @if ($a) B @endif</flux:select.option>'))->not->toBe([]);

    // Uso correcto: la directiva envuelve el componente por fuera, o se usa una expresión.
    expect(violacionesDirectivasFlux('@if ($a)<flux:button wire:confirm="y" />@endif'))->toBe([])
        ->and(violacionesDirectivasFlux('<flux:select.option value="1">{{ $x ? "a" : "b" }}</flux:select.option>'))->toBe([]);
});
