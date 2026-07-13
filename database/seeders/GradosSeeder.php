<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo compartido de grados (cinturones).
 *
 * VACÍO A PROPÓSITO. Hay dos escalas por cargar:
 *   - EscalaGrado::Estandar (For Kids / Jóvenes y Adultos)
 *   - EscalaGrado::Tigers (sistema propio de Tigers, con parches)
 *
 * TODO: cargar ambas escalas cuando el cliente confirme los grados oficiales.
 * Los grados de Tigers están POR CONFIRMAR; no sembrar datos inventados.
 * Usar updateOrCreate por (escala, nombre) para mantener la siembra idempotente.
 */
class GradosSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: pendiente de confirmar las dos escalas de grados (estándar y Tigers).
    }
}
