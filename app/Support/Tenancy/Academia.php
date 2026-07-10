<?php

namespace App\Support\Tenancy;

/**
 * Contenedor estático de la academia (tenant) activa durante el ciclo de vida
 * de la petición. Aunque BEKHO se opera como una sola academia, la costura
 * multi-tenant queda puesta para el futuro.
 */
class Academia
{
    /**
     * ID de la academia activa (null = ver todas / sin academia fijada).
     */
    protected static ?int $academiaId = null;

    /**
     * Fija la academia activa.
     */
    public static function set(?int $academiaId): void
    {
        static::$academiaId = $academiaId;
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
     * Olvida la academia activa.
     */
    public static function olvidar(): void
    {
        static::$academiaId = null;
    }
}
