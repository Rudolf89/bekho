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
    | Contexto BEKHO: se protegen las cuentas con privilegios (admin-plataforma,
    | dirección) sin imponer fricción a alumnos (incluidos menores) ni apoderados.
    |
    */

    '2fa_obligatorio_para' => ['admin-plataforma', 'direccion'],

    /*
    |--------------------------------------------------------------------------
    | Sembrar al admin con la 2FA ya confirmada (conveniencia de desarrollo)
    |--------------------------------------------------------------------------
    |
    | Si es true, RolesPermisosSeeder crea admin@bekho.cl con
    | two_factor_confirmed_at seteado, para no chocar con el muro de activación
    | de 2FA tras cada `migrate:fresh --seed`. Actívalo/desactívalo con
    | BEKHO_SEMBRAR_ADMIN_2FA en el .env.
    |
    | Por defecto: ON fuera de producción, OFF en producción (ahí se exige 2FA
    | real). NO afecta el requisito de 2FA en runtime, solo el estado inicial
    | del admin sembrado.
    |
    */

    'sembrar_admin_con_2fa' => (bool) env('BEKHO_SEMBRAR_ADMIN_2FA', env('APP_ENV') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Exámenes de grado — criterios de elegibilidad (sugeridos)
    |--------------------------------------------------------------------------
    |
    | Umbrales para SUGERIR quién puede rendir. La decisión final la confirma el
    | instructor (visto bueno manual). Si un umbral es null, ese criterio no
    | filtra (se listan todos y decide el instructor).
    |
    | POR CONFIRMAR: los valores reales aún no se conocen; NO se inventan.
    |
    */

    'examenes' => [
        'asistencia_minima_pct' => null,   // TODO: p. ej. 75 (% desde el último grado)
        'meses_minimos_en_grado' => null,  // TODO: p. ej. 4 (meses en el grado actual)
    ],

    /*
    |--------------------------------------------------------------------------
    | Premios de collar de máster (conteo en cascada de graduaciones)
    |--------------------------------------------------------------------------
    |
    | Umbrales de graduaciones acumuladas (incluida toda la línea descendente)
    | para alcanzar cada collar: Negro → Azul → Plateado → Dorado.
    |
    | Estructura placeholder: los números reales están POR CONFIRMAR. Mientras
    | sean null, el sistema calcula el total en cascada pero no otorga collares.
    | Son DISTINTOS de los collares del catálogo cargos_rangos.
    |
    */

    'premios_collar' => [
        'azul' => null,      // TODO: umbral por confirmar
        'plateado' => null,  // TODO: umbral por confirmar
        'dorado' => null,    // TODO: umbral por confirmar
    ],

    /*
    |--------------------------------------------------------------------------
    | Reglamento del alumno
    |--------------------------------------------------------------------------
    |
    | URL al documento del Reglamento del Alumno que se enlaza en la declaración
    | del formulario de inscripción. Por defecto apunta al PDF incluido en
    | public/docs; se puede reemplazar por una URL externa con BEKHO_REGLAMENTO_URL.
    |
    */

    'reglamento_url' => env('BEKHO_REGLAMENTO_URL', '/docs/reglamento-alumno.pdf'),

];
