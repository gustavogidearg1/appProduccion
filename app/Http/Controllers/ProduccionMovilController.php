<?php
namespace App\Http\Controllers;
use App\Models\EtapaProduccion;
use App\Models\PlanProduccion;
use App\Models\UnidadProduccion;
use App\Services\SectoresProduccion;
use Illuminate\Http\Request;
class ProduccionMovilController extends Controller {
    public function index(Request $request) {
        $plan=null;$unidad=null;$etapaActual=null;$mensaje=null;
        $sectores = collect();
        if($request->filled('unidad') || $request->filled('plan') || $request->filled('chasis')){
            $datos=$request->validate([
                'unidad'=>['nullable','integer','min:1'],
                'plan'=>['required_without:unidad','nullable','integer','min:1'],
                'chasis'=>['nullable','string','max:100'],
            ]);
            if(!empty($datos['unidad'])){
                $unidad=UnidadProduccion::with(['planProducto.plan','planProducto.producto','avances.etapa'])->find($datos['unidad']);
                $plan=$unidad?->planProducto?->plan;
                if(!$unidad || !$plan){$unidad=null;$mensaje='No se encontró la unidad indicada por el QR.';}
            }else{
                $plan=PlanProduccion::where('numero',$datos['plan'])->first();
                if(!$plan)$mensaje='No se encontró el plan indicado.';
                elseif (filled($datos['chasis'] ?? null)) {
                    $unidad=UnidadProduccion::with(['planProducto.producto','avances.etapa'])
                        ->where('chasis',$datos['chasis'])->whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->first();
                    if(!$unidad)$mensaje='No se encontró una unidad con ese chasis dentro del plan.';
                }
            }
            if ($plan && !$unidad && !$mensaje) {
                $plan->load('avances.etapa');
                $avances = $plan->avances->keyBy('etapa_id');
                $completadas = $plan->avances->where('estado', 'completada')->pluck('etapa.codigo');
                $etapas = EtapaProduccion::where('activo', true)->where('alcance', 'plan')
                    ->whereIn('codigo', SectoresProduccion::CODIGOS)->get();
                $sectores = SectoresProduccion::ordenar($etapas)->map(fn ($etapa) => [
                    'etapa' => $etapa,
                    'avance' => $avances->get($etapa->id),
                    'habilitada' => collect(SectoresProduccion::previas($etapa->codigo))
                        ->diff($completadas)->isEmpty(),
                ]);
            }
            if($unidad){
                $unidad->setAttribute('estimacion_entrega', (new \App\Services\EstimadorEntrega)->calcular($unidad));
                $efectivos=\App\Services\AvancesUnidad::efectivos($unidad);
                $etapaActual=$efectivos->sortBy('etapa.orden')->first(fn($a)=>$a->estado!=='completada')
                    ?? $efectivos->sortByDesc('etapa.orden')->first();
            }
        }
        return view('produccion.movil.index',compact('plan','unidad','etapaActual','mensaje','sectores'));
    }
}
