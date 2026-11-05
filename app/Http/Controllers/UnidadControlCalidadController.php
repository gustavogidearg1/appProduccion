<?php
namespace App\Http\Controllers;
use App\Models\{PlanProduccion,PlanProducto,UnidadProduccion,UnidadControlCalidad,EtapaProduccion,AvanceProduccion};
use App\Services\CalidadDocumental as C;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\ValidationException;
class UnidadControlCalidadController extends Controller {
 private function version($rows){return hash('sha256',json_encode($rows->sortBy('id')->map(fn($r)=>$r->getRawOriginal())->values()->all()));}
 public function index(PlanProduccion $plan,PlanProducto $planProducto,UnidadProduccion $unidad){
  $this->validarRelaciones($plan,$planProducto,$unidad);C::preparar($unidad);$planProducto->load('producto');
  $controles=$unidad->controlesCalidad()->with(['control','etapa'])->orderBy('id')->get();$version=$this->version($controles);
  $revision=C::revision($unidad->calidad_revision_id);$vigente=C::vigente($planProducto->producto?->familia_id);
  $historial=DB::table('calidad_respuestas_historial')->where('unidad_produccion_id',$unidad->id)->orderByDesc('id')->paginate(20);
  return view('produccion.planes.calidad.unidad',compact('plan','planProducto','unidad','controles','revision','vigente','version','historial'));
 }
 public function update(Request $q,PlanProduccion $plan,PlanProducto $planProducto,UnidadProduccion $unidad){
  $this->validarRelaciones($plan,$planProducto,$unidad);abort_if($plan->estado==='cancelado' || in_array($unidad->estado,['entregada','cancelada']),422,'El plan o la unidad está cerrado.');
  $d=$q->validate(['version'=>['required','string','size:64'],'motivo_correccion'=>['nullable','string','max:5000'],'controles'=>['required','array'],
   'controles.*.resultado'=>['required','in:pendiente,aprobado,observado,no_aplica'],'controles.*.valor'=>['nullable','string','max:2000'],
   'controles.*.observaciones'=>['nullable','string','max:5000'],'controles.*.operario'=>['nullable','string','max:150'],'controles.*.instrumento'=>['nullable','string','max:150'],
   'controles.*.criterio_medicion'=>['nullable','in:color,gris'],'controles.*.fecha_control'=>['nullable','date','before_or_equal:today'],
   'controles.*.evidencia'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120']]);
  $paths=[];
  try{DB::transaction(function()use($q,$d,$unidad,$planProducto,&$paths){
   $u=UnidadProduccion::whereKey($unidad->id)->lockForUpdate()->firstOrFail();$rows=$u->controlesCalidad()->with('control')->lockForUpdate()->get();
   if(!hash_equals($this->version($rows),$d['version']))throw ValidationException::withMessages(['version'=>'Los controles cambiaron. Recargá antes de guardar.']);
   foreach($d['controles'] as $id=>$input){$r=$rows->firstWhere('id',(int)$id);abort_unless($r,422);$def=C::definicion($r);$estado=$input['resultado'];
    $error=function($m)use($def){throw ValidationException::withMessages(['controles'=>($def['nombre']??'Control').': '.$m]);};
    $valor=trim($input['valor']??'');$obs=trim($input['observaciones']??'');
    if(in_array($estado,['observado','no_aplica']) && !$obs)$error('indicá el motivo en observaciones.');
    if($estado!=='pendiente' && (empty($input['operario']) || empty($input['fecha_control'])))$error('indicá fecha y operario / legajo.');
    if($estado==='aprobado'){
     if(($def['tipo_respuesta']??'')==='valor'){
      $n=str_replace(',','.',$valor);if(!is_numeric($n))$error('registrá una medición numérica.');$min=$def['min']??null;$max=$def['max']??null;
      if(!empty($def['espesor'])){if(empty($input['criterio_medicion']))$error('seleccioná el criterio de pintura color o gris.');[$min,$max]=$input['criterio_medicion']==='gris' ? [35,65] : [50,90];}
      if(($min!==null && (float)$n<(float)$min) || ($max!==null && (float)$n>(float)$max))$error('valor fuera de tolerancia. Registralo como Observado.');
     }elseif(($def['tipo_respuesta']??'')==='texto' && !$valor)$error('completá el valor o número de serie.');
    }
    $nuevo=['resultado'=>$estado,'valor'=>$valor?:null,'observaciones'=>$obs?:null,'operario'=>$input['operario']??null,'instrumento'=>$input['instrumento']??null,'criterio_medicion'=>$input['criterio_medicion']??null,'fecha_control'=>!empty($input['fecha_control']) ? \Carbon\Carbon::parse($input['fecha_control'])->toDateString().' 00:00:00' : null];
    $antes=$r->only(array_keys($nuevo));$antes['fecha_control']=$r->fecha_control?->format('Y-m-d H:i:s');
    $file=$q->file('controles.'.$id.'.evidencia');$cambio=$antes!=$nuevo || $file;
    if(!$cambio)continue;
    if($r->fecha_control && empty($d['motivo_correccion']))$error('para corregir un control registrado, indicá el motivo de corrección al final.');
    $anterior=$r->getAttributes();
    if($file){$path=$file->store('calidad/evidencias-v2','local');$paths[]=$path;$nuevo['evidencia_path']=$path;}
    $usuario=auth()->id();$nuevo['user_id']=$usuario && DB::table('users')->where('id',$usuario)->exists() ? $usuario : null;
    $r->update($nuevo);C::historial($u->id,$r->id,$anterior,$r->getAttributes(),$d['motivo_correccion']??'Registro de autocontrol');
   }
   $this->avanzarEtapaAutomaticamente($u,$planProducto);
  });}catch(\Throwable $e){foreach($paths as $path)Storage::disk('local')->delete($path);throw $e;}
  return back()->with('success','Controles guardados con fecha, responsable e historial.');
 }
 public function evidencia(UnidadProduccion $unidad,UnidadControlCalidad $controlUnidad){abort_unless($controlUnidad->unidad_produccion_id===$unidad->id && $controlUnidad->evidencia_path,404);$disk=str_starts_with($controlUnidad->evidencia_path,'calidad/evidencias-v2/')?'local':'public';abort_unless(Storage::disk($disk)->exists($controlUnidad->evidencia_path),404);return response()->file(Storage::disk($disk)->path($controlUnidad->evidencia_path));}
 public function evidenciaHistorica(UnidadProduccion $unidad,int $historial,string $lado){abort_unless(in_array($lado,['anterior','nuevo']),404);$h=DB::table('calidad_respuestas_historial')->where('id',$historial)->where('unidad_produccion_id',$unidad->id)->first();abort_unless($h,404);$path=json_decode($h->$lado,true)['evidencia_path']??null;abort_unless($path,404);$disk=str_starts_with($path,'calidad/evidencias-v2/')?'local':'public';abort_unless(Storage::disk($disk)->exists($path),404);return response()->file(Storage::disk($disk)->path($path));}
 private function avanzarEtapaAutomaticamente(UnidadProduccion $unidad,PlanProducto $planProducto): void {
  $avance=$unidad->avances()->with('etapa')->where('estado','en_proceso')->first();if(!$avance || $avance->etapa?->codigo==='ENTREGADO')return;
  $rows=$unidad->controlesCalidad()->with('control')->where('etapa_produccion_id',$avance->etapa_id)->get();
  $obligatorios=$rows->filter(fn($r)=>!empty(C::definicion($r)['obligatorio']));
  if($obligatorios->isEmpty() || $obligatorios->contains(fn($r)=>!in_array($r->resultado,['aprobado','no_aplica'])))return;
  if($avance->etapa->codigo==='OK_CALIDAD' && C::pendientes($unidad)->isNotEmpty())return;
  $usuario=auth()->id();$local=$usuario && DB::table('users')->where('id',$usuario)->exists()?$usuario:null;
  $avance->update(['estado'=>'completada','fecha_fin'=>now(),'finalizado_por'=>$local]);
  if($avance->etapa->codigo==='OK_CALIDAD')$unidad->update(['estado'=>'terminada']);
  // El siguiente sector se inicia explícitamente: guardar calidad no inventa su inicio.
 }
    private function validarRelaciones(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ): void {
        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }
    }
}
