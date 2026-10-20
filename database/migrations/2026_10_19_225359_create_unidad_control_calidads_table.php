<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    if (!Schema::hasTable('unidad_controles_calidad')) {
        Schema::create('unidad_controles_calidad', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unidad_produccion_id')
                ->constrained('unidades_produccion')
                ->cascadeOnDelete();

            $table->foreignId('control_calidad_id')
                ->constrained('controles_calidad')
                ->cascadeOnDelete();

            $table->string('resultado')
                ->default('pendiente');

            $table->text('valor')->nullable();
            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamp('fecha_control')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'unidad_produccion_id',
                    'control_calidad_id',
                ],
                'unidad_control_unica'
            );
        });

        return;
    }

    Schema::table('unidad_controles_calidad', function (Blueprint $table) {
        $table->unique(
            [
                'unidad_produccion_id',
                'control_calidad_id',
            ],
            'unidad_control_unica'
        );
    });
}

    public function down(): void
    {
        Schema::dropIfExists('unidad_controles_calidad');
    }
};
