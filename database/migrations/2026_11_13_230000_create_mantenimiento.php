<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mantenimiento_maquinas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id')->index();
            $t->string('numero', 60); $t->string('nombre'); $t->string('marca')->nullable();
            $t->string('sector')->nullable(); $t->unsignedTinyInteger('grado')->default(3);
            $t->string('estado', 30)->default('activa'); $t->text('observaciones')->nullable();
            $t->unsignedInteger('version')->default(1); $t->timestamps();
            $t->unique(['company_id', 'numero']);
        });
        Schema::create('mantenimiento_tareas', function (Blueprint $t) {
            $t->id(); $t->foreignId('maquina_id')->constrained('mantenimiento_maquinas')->restrictOnDelete();
            $t->string('nombre'); $t->text('instrucciones')->nullable();
            $t->unsignedInteger('frecuencia_dias')->nullable(); $t->string('frecuencia_detalle')->nullable();
            $t->date('fecha_inicial')->nullable(); $t->boolean('activa')->default(true);
            $t->unsignedInteger('version')->default(1); $t->timestamps();
        });
        Schema::create('mantenimiento_registros', function (Blueprint $t) {
            $t->id(); $t->foreignId('tarea_id')->constrained('mantenimiento_tareas')->restrictOnDelete();
            $t->date('fecha'); $t->text('intervencion'); $t->text('repuestos')->nullable();
            $t->decimal('horas', 10, 2)->nullable(); $t->unsignedBigInteger('autor_id');
            $t->string('autor_nombre'); $t->timestamp('anulado_en')->nullable();
            $t->unsignedBigInteger('anulado_por')->nullable(); $t->text('motivo_anulacion')->nullable();
            $t->timestamps(); $t->index(['tarea_id', 'fecha']);
        });
        Schema::create('mantenimiento_ordenes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id')->index();
            $t->foreignId('maquina_id')->nullable()->constrained('mantenimiento_maquinas')->restrictOnDelete();
            $t->string('titulo'); $t->text('descripcion'); $t->string('prioridad', 15)->default('media');
            $t->string('estado', 30)->default('pendiente'); $t->unsignedBigInteger('autor_id');
            $t->string('autor_nombre'); $t->unsignedBigInteger('responsable_id')->nullable();
            $t->string('responsable_nombre')->nullable(); $t->date('fecha_compromiso')->nullable();
            $t->date('fecha_inicio')->nullable(); $t->date('fecha_fin')->nullable();
            $t->unsignedInteger('version')->default(1); $t->timestamps();
        });
        Schema::create('mantenimiento_movimientos', function (Blueprint $t) {
            $t->id(); $t->foreignId('orden_id')->constrained('mantenimiento_ordenes')->restrictOnDelete();
            $t->string('accion', 30); $t->text('detalle'); $t->date('fecha');
            $t->text('repuestos')->nullable(); $t->decimal('horas', 10, 2)->nullable();
            $t->unsignedBigInteger('autor_id'); $t->string('autor_nombre'); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['movimientos','ordenes','registros','tareas','maquinas'] as $tabla) Schema::dropIfExists('mantenimiento_'.$tabla);
    }
};
