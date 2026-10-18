<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_componentes', function (Blueprint $table) {
            $table->id();

            // Producto principal del Cotizador.
            $table
                ->unsignedBigInteger('producto_id')
                ->index();

            // Accesorio o componente del Cotizador.
            $table
                ->unsignedBigInteger('componente_producto_id')
                ->index();

            $table
                ->decimal('cantidad', 10, 2)
                ->default(1);

            $table
                ->boolean('obligatorio')
                ->default(false);

            $table
                ->boolean('activo')
                ->default(true);

            $table
                ->string('uso', 30)
                ->default('definitivo');

            $table
                ->text('observaciones')
                ->nullable();

            $table
                ->unique([
                    'producto_id',
                    'componente_producto_id',
                ]);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_componentes');
    }
};
