<?php

use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

/**
 * Simula un teléfono con el reloj desfasado respecto del servidor y comprueba
 * que la ventana (config fortify-options...window) tolera ese desfase.
 */
function codigoDesfasado(Google2FA $g, string $secret, int $segundos): string
{
    $contador = intdiv(time() + $segundos, 30);

    return $g->oathTotp($secret, $contador);
}

test('con ventana por defecto, un código desfasado ~14 min es rechazado', function () {
    config(['fortify-options.two-factor-authentication.window' => 1]);

    $g = app(Google2FA::class);
    $secret = $g->generateSecretKey();
    $codigo = codigoDesfasado($g, $secret, -14 * 60); // teléfono 14 min atrás

    expect(app(TwoFactorAuthenticationProvider::class)->verify($secret, $codigo))->toBeFalse();
});

test('con ventana amplia (30), el mismo código desfasado ~14 min es aceptado', function () {
    config(['fortify-options.two-factor-authentication.window' => 30]);

    $g = app(Google2FA::class);
    $secret = $g->generateSecretKey();
    $codigo = codigoDesfasado($g, $secret, -14 * 60);

    expect(app(TwoFactorAuthenticationProvider::class)->verify($secret, $codigo))->toBeTrue();
});
