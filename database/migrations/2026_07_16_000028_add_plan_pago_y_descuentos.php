<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes de pago del reglamento: la matrícula tiene un plan (mensual, semestral o
 * anual) y cada sede define el descuento (%) de los planes semestral y anual sobre
 * el arancel base. Sin valores por defecto inventados (0 = sin descuento hasta que
 * la sede lo configure).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('plan_pago')->default('mensual')->after('dia_vencimiento'); // App\Enums\PlanPago
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->unsignedTinyInteger('descuento_semestral_pct')->default(0)->after('capacidad');
            $table->unsignedTinyInteger('descuento_anual_pct')->default(0)->after('descuento_semestral_pct');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn('plan_pago');
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn(['descuento_semestral_pct', 'descuento_anual_pct']);
        });
    }
};
