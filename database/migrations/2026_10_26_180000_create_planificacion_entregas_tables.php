<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('producto_plazos_entrega', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('producto_id');
            $t->foreignId('etapa_id')->constrained('etapas_produccion');
            $t->unsignedInteger('dias_habiles'); $t->boolean('desde_hoy')->default(false);
            $t->boolean('activo')->default(true); $t->timestamps();
            $t->unique(['producto_id','etapa_id']);
        });
        Schema::create('calendario_no_laboral', function (Blueprint $t) {
            $t->id(); $t->date('desde'); $t->date('hasta');
            $t->string('tipo',30); $t->string('motivo',255);
            $t->boolean('activo')->default(true); $t->timestamps();
            $t->index(['desde','hasta']);
        });
        Schema::create('demoras_produccion', function (Blueprint $t) {
            $t->id(); $t->foreignId('plan_produccion_id')->constrained('planes_produccion');
            $t->foreignId('unidad_produccion_id')->nullable()->constrained('unidades_produccion');
            $t->date('fecha'); $t->text('motivo'); $t->unsignedInteger('dias_habiles');
            $t->string('registrado_por'); $t->timestamp('resuelta_at')->nullable();
            $t->string('resuelta_por')->nullable(); $t->text('resolucion')->nullable(); $t->timestamps();
        });
        Schema::create('entregas_unidad', function (Blueprint $t) {
            $t->id(); $t->foreignId('unidad_produccion_id')->unique()->constrained('unidades_produccion');
            $t->foreignId('venta_asignacion_id')->constrained('venta_asignacions');
            $t->date('fecha'); $t->string('recibido_por'); $t->string('responsable');
            $t->boolean('identificacion_ok'); $t->boolean('documentacion_ok');
            $t->boolean('accesorios_ok'); $t->text('observaciones')->nullable(); $t->timestamps();
        });
        Schema::create('entrega_fotos', function (Blueprint $t) {
            $t->id(); $t->foreignId('entrega_id')->constrained('entregas_unidad');
            $t->string('path'); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('entrega_fotos'); Schema::dropIfExists('entregas_unidad');
        Schema::dropIfExists('demoras_produccion'); Schema::dropIfExists('calendario_no_laboral');
        Schema::dropIfExists('producto_plazos_entrega');
    }
};
