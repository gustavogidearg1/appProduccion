<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_asignaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_detalle_id')
                ->constrained('venta_detalles')
                ->cascadeOnDelete();

            $table->foreignId('unidad_produccion_id')
                ->constrained('unidades_produccion')
                ->restrictOnDelete();

            $table->string('estado')->default('reservada');

            $table->dateTime('fecha_reserva')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique([
                'venta_detalle_id',
                'unidad_produccion_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_asignaciones');
    }
};
