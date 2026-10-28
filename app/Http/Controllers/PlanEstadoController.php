<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\UnidadProduccion;
use App\Models\VentaAsignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PlanEstadoController extends Controller {
    public function index(PlanProduccion $plan){
        $plan->load('planProductos.producto');
        $ids=UnidadProduccion::whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->pluck('id');
        $reservadas=VentaAsignacion::whereIn('unidad_produccion_id',$ids)->whereIn('estado',['reservada','asignada'])->distinct()->count('unidad_produccion_id');
        $entregadas=UnidadProduccion::whereIn('id',$ids)->where(fn($q)=>$q->where('estado','entregada')->orWhereHas('avances',fn($a)=>$a->where('estado','completada')->whereHas('etapa',fn($e)=>$e->where('codigo','ENTREGADO'))))->count();
        $controles=DB::table('entregas_unidad')->whereIn('unidad_produccion_id',$ids)->count();
        $historial=DB::table('planes_historial')->where('plan_produccion_id',$plan->id)->orderByDesc('id')->paginate(20);
        $cantidad=$ids->count();
        return view('produccion.planes.estado',compact('plan','cantidad','reservadas','entregadas','controles','historial'));
    }
    public function update(Request $r,PlanProduccion $plan){
        $d=$r->validate(['accion'=>['required','in:anular,reactivar'],'motivo'=>['required','string','max:5000'],'confirmacion'=>['accepted']]);
        DB::transaction(function()use($plan,$d){
            $plan=PlanProduccion::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $estado=$d['accion']==='anular' ? 'cancelado' : 'abierto';
            if($plan->estado===$estado)return;
            if($d['accion']==='reactivar' && $plan->estado!=='cancelado')throw ValidationException::withMessages(['accion'=>'Solo se puede reactivar un plan anulado.']);
            $unidades=UnidadProduccion::whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->orderBy('id')->lockForUpdate()->get();
            if($d['accion']==='anular'){
                $ids=$unidades->pluck('id');
                $compromiso=VentaAsignacion::whereIn('unidad_produccion_id',$ids)->whereIn('estado',['reservada','asignada'])->lockForUpdate()->first();
                if($compromiso)throw ValidationException::withMessages(['accion'=>'El plan tiene unidades reservadas o asignadas. Primero revisá o anulá las ventas correspondientes.']);
                $entregada=$unidades->contains('estado','entregada') || DB::table('entregas_unidad')->whereIn('unidad_produccion_id',$ids)->exists()
                    || \App\Models\AvanceProduccion::whereIn('unidad_produccion_id',$ids)->where('estado','completada')->whereHas('etapa',fn($q)=>$q->where('codigo','ENTREGADO'))->exists();
                if($entregada)throw ValidationException::withMessages(['accion'=>'Hay unidades entregadas o controles de salida. Revisá esos registros antes de anular el plan.']);
            }
            DB::table('planes_historial')->insert(['plan_produccion_id'=>$plan->id,'accion'=>$d['accion'],
                'estado_anterior'=>$plan->estado,'estado_nuevo'=>$estado,'motivo'=>$d['motivo'],
                'responsable'=>auth()->user()?->name ?? 'Usuario autenticado','usuario_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
            $plan->update(['estado'=>$estado]);
        });
        return back()->with('success',$d['accion']==='anular' ? 'Plan anulado. Sus registros se conservan y sus unidades no se ofrecen en Ventas.' : 'Plan reactivado. Sus unidades y avances conservan sus estados anteriores.');
    }
}
