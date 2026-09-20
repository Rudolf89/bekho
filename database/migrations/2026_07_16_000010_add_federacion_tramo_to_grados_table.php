<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 3: los grados pasan a ser catálogo de la federación y se enlazan
 * al tramo de entrenamiento. Se añaden meses_sugeridos (tiempo entre grados: 2 en
 * los de color) y requiere_nominacion (obligatoria en rojo-negro y danes). Aditivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->foreignId('federacion_id')->nullable()->after('id')->constrained('federaciones')->nullOnDelete();
            $table->foreignId('tramo_id')->nullable()->after('escala')->constrained('tramos_entrenamiento')->nullOnDelete();
            $table->unsignedSmallInteger('meses_sugeridos')->nullable()->after('estrellas');
            $table->boolean('requiere_nominacion')->default(false)->after('meses_sugeridos');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('federacion_id');
            $table->dropConstrainedForeignId('tramo_id');
            $table->dropColumn(['meses_sugeridos', 'requiere_nominacion']);
        });
    }
};
