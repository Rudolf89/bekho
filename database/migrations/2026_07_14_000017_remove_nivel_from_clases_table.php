<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Las clases se dividen SOLO por grupo etario (Tigers, For Kids, Jóvenes y
     * Adultos), no por nivel de entrenamiento. Se elimina la columna nivel.
     */
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropColumn('nivel');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->string('nivel')->default('principiantes');
        });
    }
};
