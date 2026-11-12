<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calidad_hallazgo_secuencias', function (Blueprint $t) {
            $t->string('tipo', 4)->primary();
            $t->unsignedInteger('ultimo')->default(0);
        });
        foreach (['NC', 'OBS', 'OM'] as $tipo) DB::table('calidad_hallazgo_secuencias')->insert(['tipo' => $tipo, 'ultimo' => 0]);
        Schema::create('calidad_hallazgos', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 4);
            $t->unsignedInteger('numero');
            $t->unique(['tipo', 'numero']);
            $t->string('origen', 60);
            $t->string('titulo', 200);
            $t->text('descripcion');
            // Los usuarios pertenecen a Club Comofra, en otra conexión.
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('autor_id')->index();
            $t->unsignedBigInteger('responsable_id')->index();
            $t->foreignId('plan_produccion_id')->nullable()->constrained('planes_produccion')->restrictOnDelete();
            $t->foreignId('unidad_produccion_id')->nullable()->constrained('unidades_produccion')->restrictOnDelete();
            $t->string('estado', 24)->default('abierto')->index();
            $t->text('causa')->nullable();
            $t->text('contencion')->nullable();
            $t->text('accion')->nullable();
            $t->date('fecha_compromiso')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('calidad_hallazgo_movimientos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hallazgo_id')->constrained('calidad_hallazgos')->restrictOnDelete();
            $t->unsignedBigInteger('usuario_id');
            $t->string('accion', 30);
            $t->string('estado_anterior', 24)->nullable();
            $t->string('estado_nuevo', 24);
            $t->text('detalle');
            $t->string('foto')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('calidad_hallazgo_movimientos');
        Schema::dropIfExists('calidad_hallazgos');
        Schema::dropIfExists('calidad_hallazgo_secuencias');
    }
};
