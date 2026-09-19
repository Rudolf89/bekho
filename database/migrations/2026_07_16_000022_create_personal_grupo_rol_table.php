<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 7b: roles del personal por grupo y, opcionalmente, por sede.
 * Una persona que trabaja en un grupo (personal_grupo) puede tener varios roles;
 * con sede_id el rol queda acotado a esa sede (p. ej. direccion-sede). Capa de
 * datos aditiva: la aplicación de spatie en modo teams se resolverá aparte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_grupo_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_grupo_id')->constrained('personal_grupo')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->timestamps();

            // Un rol por sede (o global en el grupo con sede nula) sin duplicar.
            $table->unique(['personal_grupo_id', 'role_id', 'sede_id']);
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_grupo_rol');
    }
};
