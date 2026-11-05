<?php
namespace App\Services;
use App\Models\{Familia,CalidadSeccion,FamiliaControlCalidad};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ConfiguracionCalidad {
 public static function estado(Familia $f): array {
  $doc=DB::table('calidad_documentos')->where('familia_id',$f->id)->first();
  $r=$doc ? DB::table('calidad_revisiones')->where('documento_id',$doc->id)->where('estado','borrador')->first() : null;
  if(!$r && $doc)$r=DB::table('calidad_revisiones')->where('documento_id',$doc->id)->where('estado','vigente')->first();
  $secciones=CalidadSeccion::where('familia_id',$f->id)->orderBy('orden')->orderBy('id')->get();
  $c=$r ? json_decode($r->contenido,true) : ['items'=>[],'imagenes'=>[]];
  if(!$r){foreach(FamiliaControlCalidad::with('control')->where('familia_id',$f->id)->where('activo',true)->orderBy('orden')->get() as $l){$c['items'][]=['control_id'=>$l->control_calidad_id,'nombre'=>$l->control->nombre,'descripcion'=>$l->control->descripcion,'tipo_respuesta'=>$l->control->tipo_respuesta,'etapa_id'=>$l->etapa_produccion_id,'orden'=>$l->orden,'obligatorio'=>$l->obligatorio,'activo'=>true,'seccion_id'=>$l->control->calidad_seccion_id,'seccion'=>'Controles','min'=>$l->valor_minimo,'max'=>$l->valor_maximo,'unidad_medida'=>$l->unidad_medida];}}
  foreach($c['items'] as &$i){$s=$secciones->firstWhere('id',$i['seccion_id']??null) ?? $secciones->firstWhere('nombre',$i['seccion']??'');if($s){$i['seccion_id']=$s->id;$i['seccion']=$s->nombre;$i['seccion_orden']=$s->orden;$i['seccion_activa']=$s->activo;}$i['activo']=$i['activo']??true;}unset($i);
  $version=hash('sha256',json_encode([$r?->id,$r?->contenido,$r?->updated_at,$secciones->toArray()]));
  return compact('doc','r','c','secciones','version');
 }
 public static function guardar(Familia $f,string $version,callable $editar): void {
  DB::transaction(function()use($f,$version,$editar){
   // Serialize every edit for a family, including its first working copy.
   DB::table('calidad_secciones')->where('familia_id',$f->id)->orderBy('id')->lockForUpdate()->get();
   $doc=DB::table('calidad_documentos')->where('familia_id',$f->id)->lockForUpdate()->first();$e=self::estado($f);
   if(!hash_equals($e['version'],$version))throw ValidationException::withMessages(['version'=>'La configuración cambió en otra pantalla. Recargá antes de guardar para no sobrescribir cambios.']);
   $c=$e['c'];$editar($c,$e);
   if(!$doc){$id=DB::table('calidad_documentos')->insertGetId(['familia_id'=>$f->id,'codigo'=>'SIN DEFINIR','nombre'=>'Planilla de autocontrol '.$f->nombre,'created_at'=>now(),'updated_at'=>now()]);$doc=DB::table('calidad_documentos')->find($id);}
   $r=$e['r'];
   if(!$r || $r->estado!=='borrador'){$n=DB::table('calidad_revisiones')->where('documento_id',$doc->id)->max('numero')??0;$id=DB::table('calidad_revisiones')->insertGetId(['documento_id'=>$doc->id,'numero'=>$n+1,'estado'=>'borrador','contenido'=>json_encode($c),'preparado_por'=>auth()->user()?->name??'Sistema','created_at'=>now(),'updated_at'=>now()]);}
   else DB::table('calidad_revisiones')->where('id',$r->id)->update(['contenido'=>json_encode($c),'updated_at'=>now()]);
  });
 }
 public static function ordenar(array $items): array {return collect($items)->sortBy(fn($i)=>sprintf('%010d-%010d',(int)($i['seccion_orden']??0),(int)($i['orden']??0)))->values()->all();}
}
