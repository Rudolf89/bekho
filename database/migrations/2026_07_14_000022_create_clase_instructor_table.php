<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asignación de instructores a clases (varios por clase, con su papel).
 *
 * Una clase puede tener más de un instructor (p. ej. titular + ayudante). Los
 * alumnos que un instructor puede ver dependen de en qué clases está asignado,
 * no de un atributo del usuario. Migra el instructor_id existente como titular.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clase_instructor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('papel')->default('titular'); // App\Enums\PapelEnClase
            $table->timestamps();

            $table->unique(['clase_id', 'user_id']);
        });

        // Migra el instructor a cargo actual como titular de cada clase.
        $clases = DB::table('clases')->whereNotNull('instructor_id')->get(['id', 'instructor_id']);
        foreach ($clases as $clase) {
            DB::table('clase_instructor')->insertOrIgnore([
                'clase_id' => $clase->id,
                'user_id' => $clase->instructor_id,
                'papel' => 'titular',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clase_instructor');
    }
};
