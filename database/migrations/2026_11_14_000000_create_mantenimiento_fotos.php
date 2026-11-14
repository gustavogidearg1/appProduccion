<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('mantenimiento_fotos',function(Blueprint $t){$t->id();$t->foreignId('orden_id')->constrained('mantenimiento_ordenes')->restrictOnDelete();$t->string('tipo',10);$t->string('ruta');$t->unsignedBigInteger('autor_id');$t->string('autor_nombre');$t->timestamps();}); }
 public function down(): void { Schema::dropIfExists('mantenimiento_fotos'); }
};
