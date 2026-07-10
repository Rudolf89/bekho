<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('academia_id')->nullable()->after('id')
                ->constrained('academias')->nullOnDelete();
            $table->foreignId('rango_id')->nullable()->after('academia_id')
                ->constrained('cargos_rangos')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('rango_id')
                ->constrained('users')->nullOnDelete();
            $table->string('telefono')->nullable()->after('email');
            $table->boolean('activo')->default(true)->after('telefono');

            $table->index('academia_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropForeign(['rango_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex(['academia_id']);
            $table->dropColumn(['academia_id', 'rango_id', 'supervisor_id', 'telefono', 'activo']);
        });
    }
};
