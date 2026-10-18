<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_produccion', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('plan_producto_id')
                ->constrained('plan_productos')
                ->cascadeOnDelete();

            $table->unsignedInteger('numero_unidad');

            $table
                ->string('chasis', 100)
                ->nullable()
                ->unique();

            $table
                ->string('serie', 100)
                ->nullable()
                ->unique();

            $table
                ->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();

            $table
                ->string('estado', 50)
                ->default('planificada');

            $table
                ->string('color', 100)
                ->nullable();

            $table
                ->text('observaciones')
                ->nullable();

            $table
                ->unique([
                    'plan_producto_id',
                    'numero_unidad',
                ]);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_produccion');
    }
};
