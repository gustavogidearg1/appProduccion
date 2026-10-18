<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances_produccion', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('etapa_id')
                ->constrained('etapas_produccion')
                ->cascadeOnDelete();

            $table
                ->foreignId('plan_produccion_id')
                ->nullable()
                ->constrained('planes_produccion')
                ->cascadeOnDelete();

            $table
                ->foreignId('unidad_produccion_id')
                ->nullable()
                ->constrained('unidades_produccion')
                ->cascadeOnDelete();

            $table
                ->string('estado', 30)
                ->default('pendiente');

            $table
                ->dateTime('fecha_inicio')
                ->nullable();

            $table
                ->dateTime('fecha_fin')
                ->nullable();

            // Usuarios pertenecientes a la base Club-Comofra.
            // No llevan clave foránea local.
            $table
                ->unsignedBigInteger('iniciado_por')
                ->nullable()
                ->index();

            $table
                ->unsignedBigInteger('finalizado_por')
                ->nullable()
                ->index();

            $table
                ->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index([
                'etapa_id',
                'plan_produccion_id',
            ]);

            $table->index([
                'etapa_id',
                'unidad_produccion_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_produccion');
    }
};
