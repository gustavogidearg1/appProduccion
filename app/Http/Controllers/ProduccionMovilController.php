<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\UnidadProduccion;
use Illuminate\Http\Request;
class ProduccionMovilController extends Controller {
    public function index(Request $request) {
        $plan=null;$unidad=null;$etapaActual=null;$mensaje=null;
        if($request->filled('unidad') || $request->filled('plan') || $request->filled('chasis')){
            $datos=$request->validate([
                'unidad'=>['nullable','integer','min:1'],
                'plan'=>['required_without:unidad','nullable','integer','min:1'],
                'chasis'=>['required_without:unidad','nullable','string','max:100'],
            ]);
            if(!empty($datos['unidad'])){
                $unidad=UnidadProduccion::with(['planProducto.plan','planProducto.producto','avances.etapa'])->find($datos['unidad']);
                $plan=$unidad?->planProducto?->plan;
                if(!$unidad || !$plan){$unidad=null;$mensaje='No se encontró la unidad indicada por el QR.';}
            }else{
                $plan=PlanProduccion::where('numero',$datos['plan'])->first();
                if(!$plan)$mensaje='No se encontró el plan indicado.';
                else{
                    $unidad=UnidadProduccion::with(['planProducto.producto','avances.etapa'])
                        ->where('chasis',$datos['chasis'])->whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->first();
                    if(!$unidad)$mensaje='No se encontró una unidad con ese chasis dentro del plan.';
                }
            }
            if($unidad){
                $unidad->setAttribute('estimacion_entrega', (new \App\Services\EstimadorEntrega)->calcular($unidad));
                $etapaActual=$unidad->avances->sortBy('etapa.orden')->first(fn($a)=>$a->estado!=='completada')
                    ?? $unidad->avances->sortByDesc('etapa.orden')->first();
            }
        }
        return view('produccion.movil.index',compact('plan','unidad','etapaActual','mensaje'));
    }
}
