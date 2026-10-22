<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familia_controles_calidad', function (Blueprint $table) {
            $table->foreignId('etapa_produccion_id')
                ->nullable()
                ->after('familia_id')
                ->constrained('etapas_produccion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('familia_controles_calidad', function (Blueprint $table) {
            $table->dropForeign([
                'etapa_produccion_id',
            ]);

            $table->dropColumn('etapa_produccion_id');
        });
    }
};
