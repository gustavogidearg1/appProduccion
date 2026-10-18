<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapas_produccion', function (Blueprint $table) {
            $table->id();

            $table
                ->string('codigo', 50)
                ->unique();

            $table
                ->string('nombre', 100);

            $table
                ->unsignedInteger('orden')
                ->default(0);

            $table
                ->string('alcance', 20)
                ->default('unidad');

            $table
                ->boolean('activo')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapas_produccion');
    }
};
