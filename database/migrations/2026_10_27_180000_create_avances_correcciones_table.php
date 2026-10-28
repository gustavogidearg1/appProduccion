<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('avances_correcciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unidad_produccion_id')->constrained('unidades_produccion');
            $t->foreignId('etapa_id')->constrained('etapas_produccion');
            $t->text('motivo'); $t->string('responsable');
            $t->unsignedBigInteger('usuario_id')->nullable();
            $t->json('anterior'); $t->json('nuevo'); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('avances_correcciones'); }
};
