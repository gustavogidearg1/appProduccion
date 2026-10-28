<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\UnidadProduccion;
use App\Models\EtapaProduccion;
use App\Models\AvanceProduccion;
use App\Models\UnidadControlCalidad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvanceCorreccionController extends Controller {
    private function validar($plan,$producto,$unidad,$etapa): void {
        abort_unless($producto->plan_produccion_id === $plan->id && $unidad->plan_producto_id === $producto->id && $etapa->alcance === 'unidad' && $etapa->activo,404);
    }
    private function version($unidad,$avances): string {
        return hash('sha256',json_encode([$unidad->getRawOriginal(),$avances->sortBy('id')->map(fn($a)=>$a->getRawOriginal())->values()->all()]));
    }
    public function edit(PlanProduccion $plan,PlanProducto $planProducto,UnidadProduccion $unidad,EtapaProduccion $etapa) {
        $this->validar($plan,$planProducto,$unidad,$etapa);
        $planProducto->load('producto');
        $avances=$unidad->avances()->with('etapa')->get();
        $avance=$avances->firstWhere('etapa_id',$etapa->id);
        $version=$this->version($unidad,$avances);
        $posteriores=$avances->filter(fn($a)=>$a->etapa?->orden>$etapa->orden && ($a->estado!=='pendiente' || $a->fecha_inicio || $a->fecha_fin));
        $bloqueada=DB::table('entregas_unidad')->where('unidad_produccion_id',$unidad->id)->exists();
        return view('produccion.planes.avances.corregir',compact('plan','planProducto','unidad','etapa','avance','version','posteriores','bloqueada'));
    }
    public function update(Request $r,PlanProduccion $plan,PlanProducto $planProducto,UnidadProduccion $unidad,EtapaProduccion $etapa) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de corregir avances.');

        $this->validar($plan,$planProducto,$unidad,$etapa);
        $d=$r->validate([
            'estado'=>['required','in:pendiente,en_proceso,completada'],
            'fecha_inicio'=>['nullable','date'], 'fecha_fin'=>['nullable','date'],
            'observaciones'=>['nullable','string','max:5000'], 'motivo'=>['required','string','max:5000'],
            'version'=>['required','string','size:64'], 'reiniciar_posteriores'=>['nullable','boolean'],
        ]);
        $inicio=!empty($d['fecha_inicio']) ? Carbon::parse($d['fecha_inicio']) : null;
        $fin=!empty($d['fecha_fin']) ? Carbon::parse($d['fecha_fin']) : null;
        $error=function($texto){throw ValidationException::withMessages(['correccion'=>$texto]);};
        if($etapa->codigo==='ENTREGADO' && $d['estado']!=='pendiente') $error('La entrega se confirma desde Control de entrega y fotos. Aquí solo puede volver a pendiente una entrega anterior sin control.');
        if($d['estado']==='pendiente' && ($inicio || $fin)) $error('Una etapa pendiente no debe tener fechas reales. Borrá inicio y finalización.');
        if($d['estado']!=='pendiente' && !$inicio) $error('Indicá el inicio real de la etapa.');
        if($d['estado']==='en_proceso' && $fin) $error('Una etapa en proceso no debe tener fecha de finalización.');
        if($d['estado']==='completada' && !$fin) $error('Una etapa completada requiere fecha de finalización.');
        if($inicio && $fin && $fin->lt($inicio)) $error('La finalización no puede ser anterior al inicio.');
        if(($inicio && $inicio->isFuture()) || ($fin && $fin->isFuture())) $error('Las fechas reales no pueden estar en el futuro.');
        DB::transaction(function()use($unidad,$etapa,$d,$inicio,$fin,$error){
            $unidad=UnidadProduccion::whereKey($unidad->id)->lockForUpdate()->firstOrFail();
            if(DB::table('entregas_unidad')->where('unidad_produccion_id',$unidad->id)->exists()) $error('La unidad tiene un control de entrega registrado y no puede corregirse desde esta pantalla.');
            if($unidad->estado==='cancelada') $error('No se pueden corregir avances de una unidad cancelada.');
            $avances=$unidad->avances()->with('etapa')->lockForUpdate()->get();
            if(!hash_equals($this->version($unidad,$avances),$d['version'])) $error('Los avances cambiaron desde que abriste el formulario. Volvé al listado y abrí la corrección nuevamente.');
            $avance=$avances->firstWhere('etapa_id',$etapa->id);
            $posteriores=$avances->filter(fn($a)=>$a->etapa?->orden>$etapa->orden && ($a->estado!=='pendiente' || $a->fecha_inicio || $a->fecha_fin));
            if($d['estado']!=='completada' && $posteriores->isNotEmpty() && empty($d['reiniciar_posteriores'])) $error('Confirmá el reinicio de las etapas posteriores para retroceder este avance.');
            if($d['estado']!=='pendiente') {
                $previas=EtapaProduccion::where('activo',true)->where('alcance','unidad')->where('orden','<',$etapa->orden)->get();
                foreach($previas as $previa){
                    $a=$avances->firstWhere('etapa_id',$previa->id);
                    if(!$a || $a->estado!=='completada') $error('Debe completarse '.$previa->nombre.' antes de avanzar esta etapa.');
                    if($a->fecha_fin && $inicio->lt($a->fecha_fin)) $error('El inicio no puede ser anterior a la finalización de '.$previa->nombre.'.');
                }
            }
            if($d['estado']==='completada'){
                foreach($posteriores as $a) if($a->fecha_inicio && $fin->gt($a->fecha_inicio)) $error('La finalización no puede superar el inicio de una etapa posterior. Corregí primero las fechas posteriores.');
                if($etapa->codigo==='OK_CALIDAD' && UnidadControlCalidad::where('unidad_produccion_id',$unidad->id)->where('resultado','pendiente')->whereHas('control',fn($q)=>$q->where('obligatorio',true))->exists()) $error('Hay controles de calidad obligatorios pendientes.');
            }
            $antes=['estado_unidad'=>$unidad->estado,'etapa'=>$avance?->getAttributes(), 'posteriores'=>[]];
            if($d['estado']!=='completada') {
                foreach($posteriores as $a){
                    $antes['posteriores'][]=$a->getAttributes();
                    $a->update(['estado'=>'pendiente','fecha_inicio'=>null,'fecha_fin'=>null,'iniciado_por'=>null,'finalizado_por'=>null]);
                }
            }
            $usuario=auth()->id();
            $usuarioLocal=$usuario && DB::table('users')->where('id',$usuario)->exists() ? $usuario : null;
            $avance ??= new AvanceProduccion(['unidad_produccion_id'=>$unidad->id,'etapa_id'=>$etapa->id]);
            $avance->fill(['estado'=>$d['estado'],'fecha_inicio'=>$inicio,'fecha_fin'=>$fin,'observaciones'=>$d['observaciones']??null,
                'iniciado_por'=>$inicio ? ($avance->iniciado_por ?? $usuarioLocal) : null,
                'finalizado_por'=>$fin ? ($avance->finalizado_por ?? $usuarioLocal) : null]);
            $avance->save();
            $actuales=$unidad->avances()->with('etapa')->get();
            $entregada=$actuales->contains(fn($a)=>$a->etapa?->codigo==='ENTREGADO' && $a->estado==='completada');
            $ok=$actuales->contains(fn($a)=>$a->etapa?->codigo==='OK_CALIDAD' && $a->estado==='completada');
            $enProduccion=$actuales->contains(fn($a)=>in_array($a->estado,['en_proceso','completada']));
            $unidad->update(['estado'=>$entregada ? 'entregada' : ($ok ? 'terminada' : ($enProduccion ? 'en_produccion' : 'planificada'))]);
            $despues=['estado_unidad'=>$unidad->estado,'etapa'=>$avance->getAttributes(), 'posteriores'=>$actuales->whereIn('id',$posteriores->pluck('id'))->map(fn($a)=>$a->getAttributes())->values()->all()];
            DB::table('avances_correcciones')->insert(['unidad_produccion_id'=>$unidad->id,'etapa_id'=>$etapa->id,
                'motivo'=>$d['motivo'],'responsable'=>auth()->user()?->name ?? 'Usuario autenticado','usuario_id'=>$usuario,
                'anterior'=>json_encode($antes),'nuevo'=>json_encode($despues),'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('planes.productos.unidades.avances',[$plan,$planProducto,$unidad])->with('success','Avance corregido. Se conservó el estado anterior y el motivo en el historial.');
    }
}
