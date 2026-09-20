<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra los permisos del módulo (Aprender + Legacy → "Programas"). Se renombra
 * la fila de `permissions` (no se borra ni recrea), así se conservan las
 * asignaciones a roles del pivote role_has_permissions.
 *
 * En migrate:fresh es un no-op (la tabla está vacía antes de los seeders, que ya
 * usan los nombres nuevos); en un despliegue con datos, actualiza los nombres.
 */
return new class extends Migration
{
    /** @var array<string, string> viejo => nuevo */
    private array $mapa = [
        'ver formacion' => 'ver programas',
        'gestionar formacion' => 'gestionar programas',
        'gestionar legacy' => 'gestionar inscripciones',
        'aprobar legacy' => 'aprobar ascensos',
    ];

    public function up(): void
    {
        $this->renombrar($this->mapa);
    }

    public function down(): void
    {
        $this->renombrar(array_flip($this->mapa));
    }

    /**
     * @param  array<string, string>  $mapa
     */
    private function renombrar(array $mapa): void
    {
        foreach ($mapa as $desde => $hacia) {
            // Evita chocar con un nombre destino ya existente.
            if (DB::table('permissions')->where('name', $hacia)->exists()) {
                continue;
            }

            DB::table('permissions')->where('name', $desde)->update(['name' => $hacia]);
        }
    }
};
