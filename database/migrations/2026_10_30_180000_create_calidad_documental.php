<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('calidad_documentos',function(Blueprint $t){$t->id();$t->unsignedBigInteger('familia_id')->unique();$t->string('codigo');$t->string('nombre');$t->timestamps();});
  Schema::create('calidad_revisiones',function(Blueprint $t){$t->id();$t->unsignedBigInteger('documento_id')->index();$t->unsignedInteger('numero');$t->string('revision')->nullable();$t->string('estado')->default('borrador');$t->longText('contenido');$t->text('motivo')->nullable();$t->string('preparado_por');$t->string('aprobado_por')->nullable();$t->date('vigente_desde')->nullable();$t->timestamp('aprobado_at')->nullable();$t->timestamps();$t->unique(['documento_id','numero']);});
  Schema::table('unidad_controles_calidad',function(Blueprint $t){$t->longText('definicion')->nullable();$t->string('operario')->nullable();$t->string('instrumento')->nullable();$t->string('criterio_medicion')->nullable();});
  Schema::table('unidades_produccion',function(Blueprint $t){$t->unsignedBigInteger('calidad_revision_id')->nullable()->index();$t->timestamp('calidad_fijada_at')->nullable();});
  Schema::create('calidad_respuestas_historial',function(Blueprint $t){$t->id();$t->unsignedBigInteger('unidad_produccion_id')->index();$t->unsignedBigInteger('control_unidad_id')->nullable()->index();$t->string('responsable');$t->text('motivo');$t->longText('anterior');$t->longText('nuevo');$t->timestamps();});
  // Preservar la definición disponible hoy, sin atribuir una revisión histórica inexistente.
  DB::table('unidad_controles_calidad')->orderBy('id')->chunkById(200,function($rows){foreach($rows as $r){
   $c=DB::table('controles_calidad')->where('id',$r->control_calidad_id)->first();if(!$c)continue;
   $f=DB::table('familia_controles_calidad')->where('control_calidad_id',$c->id)->first();
   $def=['nombre'=>$c->nombre,'descripcion'=>$c->descripcion,'tipo_respuesta'=>$c->tipo_respuesta,'obligatorio'=>(bool)($f->obligatorio??$c->obligatorio),'orden'=>$f->orden??$c->orden,'etapa_id'=>$r->etapa_produccion_id,'seccion'=>'','condicion'=>'','unidad_medida'=>$f->unidad_medida??null,'min'=>$f->valor_minimo??null,'max'=>$f->valor_maximo??null,'respuesta_esperada'=>$f->respuesta_esperada??null];
   DB::table('unidad_controles_calidad')->where('id',$r->id)->update(['definicion'=>json_encode($def)]);
   DB::table('unidades_produccion')->where('id',$r->unidad_produccion_id)->update(['calidad_fijada_at'=>now()]);
  }});
 }
 public function down(): void {throw new RuntimeException('La reversión eliminaría registros documentales. Preservar los datos y restaurar el código por separado.');}
};
