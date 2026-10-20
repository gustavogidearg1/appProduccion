<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_controles_calidad', function (Blueprint $table) {
            $table->id();

            // ID del producto perteneciente a la base Cotizador
            $table->unsignedBigInteger('producto_id');

            $table->foreignId('control_calidad_id')
                ->constrained('controles_calidad')
                ->cascadeOnDelete();

            $table->unsignedInteger('orden')
                ->default(1);

            $table->boolean('obligatorio')
                ->default(true);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'producto_id',
                'control_calidad_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_controles_calidad');
    }
};
