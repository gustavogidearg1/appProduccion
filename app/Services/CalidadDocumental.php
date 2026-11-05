<?php
namespace App\Services;
use App\Models\{UnidadProduccion,UnidadControlCalidad,FamiliaControlCalidad,ControlCalidad};
use Illuminate\Support\Facades\DB;
class CalidadDocumental {
 public static function definicion($row): array {return $row->definicion ? json_decode($row->definicion,true) : ['nombre'=>$row->control?->nombre,'descripcion'=>$row->control?->descripcion,'tipo_respuesta'=>$row->control?->tipo_respuesta,'obligatorio'=>$row->control?->obligatorio,'etapa_id'=>$row->etapa_produccion_id,'seccion'=>'','condicion'=>'','min'=>null,'max'=>null,'unidad_medida'=>null];}
 public static function vigente($familia) {return DB::table('calidad_revisiones as r')->join('calidad_documentos as d','d.id','=','r.documento_id')->where('d.familia_id',$familia)->where('r.estado','vigente')->select('r.*','d.codigo','d.nombre')->first();}
 public static function revision($id) {return $id ? DB::table('calidad_revisiones as r')->join('calidad_documentos as d','d.id','=','r.documento_id')->where('r.id',$id)->select('r.*','d.codigo','d.nombre')->first() : null;}
 public static function preparar(UnidadProduccion $u): void {
  DB::transaction(function()use($u){
   $u=UnidadProduccion::whereKey($u->id)->lockForUpdate()->firstOrFail();if($u->calidad_fijada_at)return;
   $familia=$u->planProducto?->producto?->familia_id;$rev=self::vigente($familia);
   if($rev){$items=json_decode($rev->contenido,true)['items'];foreach($items as $item){if(!($item['activo']??true)||!($item['seccion_activa']??true))continue;$row=UnidadControlCalidad::firstOrCreate(['unidad_produccion_id'=>$u->id,'control_calidad_id'=>$item['control_id']],['etapa_produccion_id'=>$item['etapa_id'],'resultado'=>'pendiente','definicion'=>json_encode($item)]);if(!$row->definicion)$row->update(['definicion'=>json_encode($item)]);}
    $u->forceFill(['calidad_revision_id'=>$rev->id,'calidad_fijada_at'=>now()])->save();
   }else{
    foreach(FamiliaControlCalidad::with('control')->where('familia_id',$familia)->where('activo',true)->get() as $f){
     $def=['nombre'=>$f->control->nombre,'descripcion'=>$f->control->descripcion,'tipo_respuesta'=>$f->control->tipo_respuesta,'obligatorio'=>$f->obligatorio,'orden'=>$f->orden,'etapa_id'=>$f->etapa_produccion_id,'seccion'=>'','condicion'=>'','unidad_medida'=>$f->unidad_medida,'min'=>$f->valor_minimo,'max'=>$f->valor_maximo,'respuesta_esperada'=>$f->respuesta_esperada];
     UnidadControlCalidad::firstOrCreate(['unidad_produccion_id'=>$u->id,'control_calidad_id'=>$f->control_calidad_id],['etapa_produccion_id'=>$f->etapa_produccion_id,'resultado'=>'pendiente','definicion'=>json_encode($def)]);
    }
    foreach($u->controlesCalidad()->with('control')->get() as $row)if(!$row->definicion)$row->update(['definicion'=>json_encode(self::definicion($row))]);
    if($u->controlesCalidad()->exists())$u->forceFill(['calidad_fijada_at'=>now()])->save();
   }
  });$u->refresh();
 }
 public static function pendientes(UnidadProduccion $u,?int $etapa=null) {
  self::preparar($u);
  return $u->controlesCalidad()->with('control')->get()->filter(function($row)use($etapa){$d=self::definicion($row);return (!$etapa || (int)$row->etapa_produccion_id===$etapa) && !empty($d['obligatorio']) && !in_array($row->resultado,['aprobado','no_aplica']);});
 }
 public static function historial($u,$row,$antes,$nuevo,$motivo): void {DB::table('calidad_respuestas_historial')->insert(['unidad_produccion_id'=>$u,'control_unidad_id'=>$row,'responsable'=>auth()->user()?->name??'Sistema','motivo'=>$motivo,'anterior'=>json_encode($antes),'nuevo'=>json_encode($nuevo),'created_at'=>now(),'updated_at'=>now()]);}
}
