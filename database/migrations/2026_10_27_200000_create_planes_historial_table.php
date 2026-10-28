<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {Schema::create('planes_historial',function(Blueprint $t){
        $t->id();$t->foreignId('plan_produccion_id')->constrained('planes_produccion');
        $t->string('accion');$t->string('estado_anterior');$t->string('estado_nuevo');
        $t->text('motivo');$t->string('responsable');$t->unsignedBigInteger('usuario_id')->nullable();$t->timestamps();
    });}
    public function down(): void {Schema::dropIfExists('planes_historial');}
};
