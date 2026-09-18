<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de sede y si es privada. Las sedes no son todas iguales: grupo
 * (abierta al público), club (dentro de gimnasios/empresas/condominios),
 * colegio o jardín. Una sede privada solo admite alumnos que pertenezcan a esa
 * entidad (la validación de inscripción se implementará más adelante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->string('tipo')->default('grupo')->after('comuna'); // App\Enums\TipoSede
            $table->boolean('privada')->default(false)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'privada']);
        });
    }
};
