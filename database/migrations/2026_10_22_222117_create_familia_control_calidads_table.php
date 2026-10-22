<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familia_controles_calidad', function (Blueprint $table) {
            $table->id();

            // ID de la familia que pertenece a la base Cotizador.
            $table->unsignedBigInteger('familia_id');

            $table->foreignId('control_calidad_id')
                ->constrained('controles_calidad')
                ->cascadeOnDelete();

            $table->unsignedInteger('orden')->default(1);

            $table->boolean('obligatorio')->default(true);

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(
                ['familia_id', 'control_calidad_id'],
                'familia_control_unica'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familia_controles_calidad');
    }
};
