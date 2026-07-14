<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del formulario de inscripción de alumno nuevo que la ficha aún no
 * tenía: género, dirección/región/comuna, un segundo teléfono y correo,
 * nombres de apoderados (texto), día de vencimiento de la mensualidad,
 * instructor a cargo y la aceptación del reglamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('genero')->nullable()->after('fecha_nacimiento'); // App\Enums\Genero
            $table->string('direccion')->nullable()->after('genero');
            $table->string('region')->nullable()->after('direccion');
            $table->string('comuna')->nullable()->after('region');
            $table->string('apoderado_1')->nullable()->after('comuna');
            $table->string('apoderado_2')->nullable()->after('apoderado_1');
            $table->string('telefono_contacto_2')->nullable()->after('telefono_contacto');
            $table->string('email_contacto_2')->nullable()->after('email_contacto');
            $table->unsignedTinyInteger('dia_vencimiento')->nullable()->after('email_contacto_2');
            $table->foreignId('instructor_id')->nullable()->after('sede_id')->constrained('users')->nullOnDelete();
            $table->boolean('acepto_reglamento')->default(false)->after('activo');
            $table->timestamp('acepto_reglamento_at')->nullable()->after('acepto_reglamento');
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instructor_id');
            $table->dropColumn([
                'genero', 'direccion', 'region', 'comuna', 'apoderado_1', 'apoderado_2',
                'telefono_contacto_2', 'email_contacto_2', 'dia_vencimiento',
                'acepto_reglamento', 'acepto_reglamento_at',
            ]);
        });
    }
};
