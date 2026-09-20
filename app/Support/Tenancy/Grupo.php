<?php

namespace App\Support\Tenancy;

/**
 * Contenedor estático del grupo (tenant) activo durante el ciclo de vida
 * de la petición. Aunque BEKHO se opera como una soel grupo, la costura
 * multi-tenant queda puesta para el futuro.
 *
 * Hay dos usos del grupo activo:
 *  - Filtrar las lecturas (aislamiento por grupo).
 *  - Autocompletar grupo_id al crear registros.
 *
 * El admin-plataforma debe VER todo el sistema, pero al crear necesita un grupo
 * de contexto. Por eso `filtraLecturas` puede apagarse: se conserva el grupo
 * activa (para crear) pero no se filtran las lecturas (ve todos los grupos).
 */
class Grupo
{
    /**
     * ID del grupo activo (null = sin grupo fijada).
     */
    protected static ?int $grupoId = null;

    /**
     * ¿El grupo activo filtra las lecturas? (false = ve todos los grupos).
     */
    protected static bool $filtraLecturas = true;

    /**
     * Modo sistema: el aislamiento por grupo queda desactivado A PROPÓSITO
     * (seeders, comandos, jobs que necesitan ver todos los grupos). Se activa solo
     * dentro de `comoSistema()`. Sin grupo activo y SIN este modo, el scope filtra
     * a vacío (falla cerrado).
     */
    protected static bool $modoSistema = false;

    /**
     * Fija el grupo activo. Con $filtraLecturas = false se mantiene como
     * contexto para crear, pero no aísla las lecturas (el admin-plataforma ve todo).
     */
    public static function set(?int $grupoId, bool $filtraLecturas = true): void
    {
        static::$grupoId = $grupoId;
        static::$filtraLecturas = $filtraLecturas;
    }

    /**
     * Devuelve el ID del grupo activo.
     */
    public static function id(): ?int
    {
        return static::$grupoId;
    }

    /**
     * Indica si hay un grupo activo fijada.
     */
    public static function hayActiva(): bool
    {
        return static::$grupoId !== null;
    }

    /**
     * Indica si el grupo activo debe filtrar las lecturas.
     */
    public static function filtraLecturas(): bool
    {
        return static::$filtraLecturas;
    }

    /**
     * Olvida el grupo activo. NO toca el modo sistema (lo gestiona `comoSistema`).
     * Es lo que corre entre trabajos de la cola para no filtrar el tenant.
     */
    public static function olvidar(): void
    {
        static::$grupoId = null;
        static::$filtraLecturas = true;
    }

    /**
     * ¿Está activo el modo sistema (aislamiento desactivado a propósito)?
     */
    public static function esSistema(): bool
    {
        return static::$modoSistema;
    }

    /**
     * Ejecuta el callback con el aislamiento por grupo DESACTIVADO a propósito
     * (ve todos los grupos), y restaura el estado anterior al terminar, incluso si
     * el callback lanza una excepción. Salida explícita para el código de sistema:
     * seeders, comandos y jobs que deben operar sobre todos los grupos. Anidable.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function comoSistema(callable $callback): mixed
    {
        $anterior = static::$modoSistema;
        static::$modoSistema = true;

        try {
            return $callback();
        } finally {
            static::$modoSistema = $anterior;
        }
    }
}
