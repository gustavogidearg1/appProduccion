<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('mantenimiento_ordenes',function(Blueprint $t){
  $t->string('estado_liberacion',20)->nullable()->index();
  $t->text('prueba_funcionamiento')->nullable();
  $t->date('liberado_en')->nullable();
  $t->unsignedBigInteger('liberado_por')->nullable();
  $t->string('liberado_nombre')->nullable();
 }); }
 public function down(): void { Schema::table('mantenimiento_ordenes',function(Blueprint $t){$t->dropIndex(['estado_liberacion']);$t->dropColumn(['estado_liberacion','prueba_funcionamiento','liberado_en','liberado_por','liberado_nombre']);}); }
};
