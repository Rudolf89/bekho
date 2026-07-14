<?php

namespace App\Support\Tenancy;

/**
 * Contenedor estático de la academia (tenant) activa durante el ciclo de vida
 * de la petición. Aunque BEKHO se opera como una sola academia, la costura
 * multi-tenant queda puesta para el futuro.
 *
 * Hay dos usos de la academia activa:
 *  - Filtrar las lecturas (aislamiento por academia).
 *  - Autocompletar academia_id al crear registros.
 *
 * El super-admin debe VER todo el sistema, pero al crear necesita una academia
 * de contexto. Por eso `filtraLecturas` puede apagarse: se conserva la academia
 * activa (para crear) pero no se filtran las lecturas (ve todas las academias).
 */
class Academia
{
    /**
     * ID de la academia activa (null = sin academia fijada).
     */
    protected static ?int $academiaId = null;

    /**
     * ¿La academia activa filtra las lecturas? (false = ve todas las academias).
     */
    protected static bool $filtraLecturas = true;

    /**
     * Fija la academia activa. Con $filtraLecturas = false se mantiene como
     * contexto para crear, pero no aísla las lecturas (el super-admin ve todo).
     */
    public static function set(?int $academiaId, bool $filtraLecturas = true): void
    {
        static::$academiaId = $academiaId;
        static::$filtraLecturas = $filtraLecturas;
    }

    /**
     * Devuelve el ID de la academia activa.
     */
    public static function id(): ?int
    {
        return static::$academiaId;
    }

    /**
     * Indica si hay una academia activa fijada.
     */
    public static function hayActiva(): bool
    {
        return static::$academiaId !== null;
    }

    /**
     * Indica si la academia activa debe filtrar las lecturas.
     */
    public static function filtraLecturas(): bool
    {
        return static::$filtraLecturas;
    }

    /**
     * Olvida la academia activa.
     */
    public static function olvidar(): void
    {
        static::$academiaId = null;
        static::$filtraLecturas = true;
    }
}
