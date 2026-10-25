<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('avances_produccion', function (Blueprint $table) {
        $table
            ->dateTime('fecha_inicio_planificada')
            ->nullable()
            ->after('fecha_inicio');

        $table
            ->dateTime('fecha_fin_planificada')
            ->nullable()
            ->after('fecha_inicio_planificada');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avances_produccion', function (Blueprint $table) {
            //
        });
    }
};
