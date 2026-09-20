<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estrellas del cinturón negro: los danes 1º-4º usan franjas rojas; desde el 5º
 * Dan se usan estrellas (una por grado sobre el 4º).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->unsignedTinyInteger('estrellas')->default(0)->after('franjas');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn('estrellas');
        });
    }
};
