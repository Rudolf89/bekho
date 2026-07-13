<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autenticación de dos factores (2FA) obligatoria por rol
    |--------------------------------------------------------------------------
    |
    | Roles cuyos usuarios DEBEN tener 2FA confirmada para usar la aplicación.
    | Se aplica vía el middleware App\Http\Middleware\ExigeDosFactores.
    |
    | Para el resto de los roles la 2FA es opcional (los usuarios pueden
    | activarla desde la pantalla de seguridad, pero no se les exige).
    |
    | Contexto BEKHO: se protegen las cuentas con privilegios (super-admin,
    | maestro) sin imponer fricción a alumnos (incluidos menores) ni apoderados.
    |
    */

    '2fa_obligatorio_para' => ['super-admin', 'maestro'],

];
