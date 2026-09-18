<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gamificación / recompensas. El CATÁLOGO de recompensas es transversal
 * (contenido ATA compartido, sin grupo_id): franjas de conocimiento (MAK),
 * Star Tag (Tigers) y coleccionables por Habilidad de Vida. Los LOGROS (qué
 * alumno ganó qué) son datos operativos (con grupo_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Catálogo compartido (sin grupo_id).
        Schema::create('recompensas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // App\Enums\TipoRecompensa
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('habilidad_vida')->nullable(); // App\Enums\HabilidadVida (coleccionables)
            $table->string('grupo_etario')->nullable();    // App\Enums\GrupoEtario (aplicabilidad; null = todos)
            $table->string('color')->nullable();
            $table->string('emoji')->nullable();
            $table->boolean('repetible')->default(false);  // Star Tag se acumula
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Logros (operativo, con grupo_id).
        Schema::create('logros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('recompensa_id')->constrained('recompensas')->cascadeOnDelete();
            $table->foreignId('otorgado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamp('otorgado_at')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'recompensa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logros');
        Schema::dropIfExists('recompensas');
    }
};
