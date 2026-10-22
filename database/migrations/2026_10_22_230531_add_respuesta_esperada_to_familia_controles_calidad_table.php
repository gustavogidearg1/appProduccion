<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familia_controles_calidad', function (Blueprint $table) {
            $table->text('respuesta_esperada')
                ->nullable()
                ->after('etapa_produccion_id');

            $table->string('unidad_medida', 50)
                ->nullable()
                ->after('respuesta_esperada');

            $table->decimal('valor_minimo', 12, 3)
                ->nullable()
                ->after('unidad_medida');

            $table->decimal('valor_maximo', 12, 3)
                ->nullable()
                ->after('valor_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('familia_controles_calidad', function (Blueprint $table) {
            $table->dropColumn([
                'respuesta_esperada',
                'unidad_medida',
                'valor_minimo',
                'valor_maximo',
            ]);
        });
    }
};
