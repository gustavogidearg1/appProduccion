<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidad_componentes', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('unidad_produccion_id')
                ->constrained('unidades_produccion')
                ->cascadeOnDelete();

            // Producto proveniente del Cotizador.
            // No lleva clave foránea local.
            $table
                ->unsignedBigInteger('producto_id')
                ->index();

            $table
                ->decimal('cantidad', 10, 2)
                ->default(1);

            $table
                ->string('estado', 50)
                ->default('pendiente');

            $table
                ->string('uso', 30)
                ->default('definitivo');

            $table
                ->string('trazabilidad', 150)
                ->nullable();

            $table
                ->date('fecha_instalacion')
                ->nullable();

            // Usuario de la base Club-Comofra.
            // No lleva clave foránea local.
            $table
                ->unsignedBigInteger('user_id')
                ->nullable()
                ->index();

            $table
                ->text('observaciones')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_componentes');
    }
};
