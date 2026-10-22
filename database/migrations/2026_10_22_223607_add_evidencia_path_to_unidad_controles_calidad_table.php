<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidad_controles_calidad', function (Blueprint $table) {
            $table->string('evidencia_path')
                ->nullable()
                ->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('unidad_controles_calidad', function (Blueprint $table) {
            $table->dropColumn('evidencia_path');
        });
    }
};
