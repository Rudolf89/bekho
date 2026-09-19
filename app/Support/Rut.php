<?php

namespace App\Support;

/**
 * Utilidades del RUT chileno: normalización, dígito verificador (módulo 11) y
 * validación. La base de datos guarda el RUT normalizado (sin puntos, con guion
 * y dígito verificador en mayúscula, p. ej. "12345678-5").
 */
class Rut
{
    /**
     * Normaliza un RUT: quita puntos y espacios, deja el guion antes del dígito
     * verificador y el DV en mayúscula. Devuelve null si no tiene forma de RUT.
     */
    public static function normalizar(string $rut): ?string
    {
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');

        if (strlen($limpio) < 2) {
            return null;
        }

        $cuerpo = substr($limpio, 0, -1);
        $dv = substr($limpio, -1);

        if (! ctype_digit($cuerpo)) {
            return null;
        }

        return ltrim($cuerpo, '0').'-'.$dv;
    }

    /**
     * Calcula el dígito verificador (módulo 11) del cuerpo numérico.
     */
    public static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += ((int) $cuerpo[$i]) * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }

    /**
     * ¿El RUT es válido (formato + dígito verificador)?
     */
    public static function esValido(string $rut): bool
    {
        $normalizado = self::normalizar($rut);

        if ($normalizado === null) {
            return false;
        }

        [$cuerpo, $dv] = explode('-', $normalizado);

        if ($cuerpo === '' || ! ctype_digit($cuerpo)) {
            return false;
        }

        return self::digitoVerificador($cuerpo) === $dv;
    }
}
