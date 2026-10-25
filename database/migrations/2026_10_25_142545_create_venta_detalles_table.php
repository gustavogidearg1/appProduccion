<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            // Producto proveniente de la base Cotizador.
            $table
                ->unsignedBigInteger('producto_id');

            $table
                ->decimal('cantidad', 10, 2);

            $table
                ->decimal('precio_unitario', 12, 2);

            $table
                ->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
