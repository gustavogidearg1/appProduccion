<?php
namespace App\Http\Controllers;
use App\Models\{Familia,CalidadSeccion,EtapaProduccion};
use App\Services\ConfiguracionCalidad as C;
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule,ValidationException};
class ConfiguracionCalidadController extends Controller {
 public function secciones(Request $q){$familias=Familia::orderBy('nombre')->get();$familia=$q->filled('familia_id')?Familia::findOrFail($q->integer('familia_id')):null;$e=$familia?C::estado($familia):null;return view('calidad.configuracion.secciones',compact('familias','familia','e'));}
 public function seccionGuardar(Request $q,Familia $familia){$d=$q->validate(['version'=>'required|string','id'=>'nullable|integer','nombre'=>'required|string|max:255','orden'=>'required|integer|min:0|max:99999','activo'=>'nullable|boolean']);$d['nombre']=trim($d['nombre']);if(!$d['nombre'])throw ValidationException::withMessages(['nombre'=>'Ingresá un nombre.']);
  C::guardar($familia,$d['version'],function(&$c,$e)use($q,$familia,$d){$s=$q->filled('id')?CalidadSeccion::where('familia_id',$familia->id)->findOrFail($d['id']):new CalidadSeccion(['familia_id'=>$familia->id]);$previo=$s->nombre;
   if(CalidadSeccion::where('familia_id',$familia->id)->where('nombre',$d['nombre'])->when($s->exists,fn($b)=>$b->where('id','!=',$s->id))->exists())throw ValidationException::withMessages(['nombre'=>'Ya existe esa sección en esta familia.']);
   $s->fill(['nombre'=>$d['nombre'],'orden'=>$d['orden'],'activo'=>$q->boolean('activo')])->save();
   foreach($c['items'] as &$i){if(($i['seccion_id']??null)===$s->id || ($previo && ($i['seccion']??'')===$previo)){$i['seccion_id']=$s->id;$i['seccion']=$s->nombre;$i['seccion_orden']=$s->orden;$i['seccion_activa']=$s->activo;}}unset($i);
   foreach($c['imagenes'] as &$i){if($previo && ($i['seccion']??'')===$previo)$i['seccion']=$s->nombre;}unset($i);
  });return back()->with('success','Sección guardada en la preparación. No se modificaron registros anteriores.');
 }
 public function controles(Request $q,Familia $familia){$e=C::estado($familia);$items=collect($e['c']['items'])->map(fn($i,$k)=>$i+['indice'=>$k]);if(!$q->boolean('inactivos'))$items=$items->filter(fn($i)=>($i['activo']??true));$items=collect(C::ordenar($items->all()));$etapas=EtapaProduccion::pluck('nombre','id');return view('calidad.configuracion.controles',compact('familia','e','items','etapas'));}
 public function edit(Request $q,Familia $familia,?int $indice=null){$e=C::estado($familia);if($indice!==null)abort_unless(isset($e['c']['items'][$indice]),404);$item=$indice===null?[]:$e['c']['items'][$indice];$etapas=EtapaProduccion::where('alcance','unidad')->where('activo',true)->orderBy('orden')->get();return view('calidad.configuracion.item',compact('familia','e','item','indice','etapas'));}
 public function guardar(Request $q,Familia $familia,?int $indice=null){$d=$q->validate(['version'=>'required|string','nombre'=>'required|string|max:1000','seccion_id'=>['required','integer',Rule::exists(CalidadSeccion::class,'id')->where('familia_id',$familia->id)],'etapa_id'=>['required','integer',Rule::exists(EtapaProduccion::class,'id')->where('alcance','unidad')->where('activo',true)],'tipo_respuesta'=>'required|in:si_no,valor,texto','orden'=>'required|integer|min:0|max:99999','descripcion'=>'nullable|string|max:5000','condicion'=>'nullable|string|max:500','unidad_medida'=>'nullable|string|max:50','min'=>'nullable|numeric','max'=>'nullable|numeric','activo'=>'nullable|boolean','obligatorio'=>'nullable|boolean']);
  if(isset($d['min'],$d['max']) && $d['max']<$d['min'])throw ValidationException::withMessages(['max'=>'El máximo debe ser mayor o igual al mínimo.']);
  C::guardar($familia,$d['version'],function(&$c,$e)use($q,$d,$indice){if($indice!==null)abort_unless(isset($c['items'][$indice]),404);$s=$e['secciones']->firstWhere('id',(int)$d['seccion_id']);if($q->boolean('activo')&&!$s->activo)throw ValidationException::withMessages(['seccion_id'=>'Activá la sección antes de asignarle un control activo.']);$i=$indice===null?[]:$c['items'][$indice];unset($d['version']);$i=array_merge($i,$d,['seccion_id'=>$s->id,'seccion'=>$s->nombre,'seccion_orden'=>$s->orden,'seccion_activa'=>$s->activo,'activo'=>$q->boolean('activo'),'obligatorio'=>$q->boolean('obligatorio')]);if($indice===null)$c['items'][]=$i;else $c['items'][$indice]=$i;});
  return redirect()->route('calidad.familias.controles.index',$familia)->with('success','Control guardado. Podés seguir ajustando otros puntos sin cambiar el número de revisión.');
 }
}
