<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up(): void {
  Schema::create('calidad_secciones',function(Blueprint $t){$t->id();$t->unsignedBigInteger('familia_id')->index();$t->string('nombre');$t->unsignedInteger('orden')->default(10);$t->boolean('activo')->default(true);$t->timestamps();$t->unique(['familia_id','nombre']);});
  Schema::table('controles_calidad',function(Blueprint $t){$t->foreignId('calidad_seccion_id')->nullable()->constrained('calidad_secciones')->restrictOnDelete();});
  foreach(DB::table('calidad_documentos')->get() as $doc){
   $revs=DB::table('calidad_revisiones')->where('documento_id',$doc->id)->orderByDesc('numero')->get();$n=10;
   foreach($revs as $rev){$c=json_decode($rev->contenido,true);foreach($c['items']??[] as $item){$nombre=trim($item['seccion']??'')?:'Controles';$s=DB::table('calidad_secciones')->where('familia_id',$doc->familia_id)->where('nombre',$nombre)->first();
    if(!$s){$id=DB::table('calidad_secciones')->insertGetId(['familia_id'=>$doc->familia_id,'nombre'=>$nombre,'orden'=>$n,'activo'=>true,'created_at'=>now(),'updated_at'=>now()]);$n+=10;}else $id=$s->id;
    if(isset($item['control_id']))DB::table('controles_calidad')->where('id',$item['control_id'])->whereNull('calidad_seccion_id')->update(['calidad_seccion_id'=>$id]);
   }}
  }
 }
 public function down(): void {Schema::table('controles_calidad',fn(Blueprint $t)=>$t->dropConstrainedForeignId('calidad_seccion_id'));Schema::dropIfExists('calidad_secciones');}
};
