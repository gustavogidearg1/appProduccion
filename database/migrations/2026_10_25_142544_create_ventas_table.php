<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->date('fecha');
            $table
                ->string('estado')
                ->default('borrador');

            $table
                ->decimal('total', 12, 2)
                ->default(0);

            $table
                ->text('observaciones')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
