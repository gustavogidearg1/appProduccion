<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_produccion', function (Blueprint $table) {
            $table
                ->date('fecha_fin_planificada')
                ->nullable()
                ->after('fecha_planificada');
        });
    }

    public function down(): void
    {
        Schema::table('planes_produccion', function (Blueprint $table) {
            $table->dropColumn('fecha_fin_planificada');
        });
    }
};
