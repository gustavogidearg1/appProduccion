<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_produccion_id')
                ->constrained('planes_produccion')
                ->cascadeOnDelete();

            // ID del producto que vive en la base de Cotizador
            $table->unsignedBigInteger('producto_id');

            $table->decimal('cantidad', 10, 2)->default(1);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_productos');
    }
};
