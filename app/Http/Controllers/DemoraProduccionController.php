<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\UnidadProduccion;
use App\Services\EstimadorEntrega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DemoraProduccionController extends Controller {
    public function index(Request $r,PlanProduccion $plan) {
        $r->validate(['unidad_id'=>['nullable','integer']]);
        $unidad=null;
        if($r->filled('unidad_id')) $unidad=UnidadProduccion::whereKey($r->unidad_id)->whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->firstOrFail();
        $demoras=DB::table('demoras_produccion')->where('plan_produccion_id',$plan->id)
            ->when($unidad,fn($q)=>$q->where(fn($s)=>$s->whereNull('unidad_produccion_id')->orWhere('unidad_produccion_id',$unidad->id)))
            ->orderByDesc('id')->get();
        return view('planificacion.demoras',compact('plan','unidad','demoras'));
    }
    public function guardar(Request $r,PlanProduccion $plan) {
        $d=$r->validate(['unidad_produccion_id'=>['nullable','integer'],'fecha'=>['required','date','before_or_equal:'.now(EstimadorEntrega::ZONA)->toDateString()],
            'motivo'=>['required','string','max:5000'],'dias_habiles'=>['required','integer','min:0','max:3650']]);
        if(!empty($d['unidad_produccion_id'])) UnidadProduccion::whereKey($d['unidad_produccion_id'])->whereHas('planProducto',fn($q)=>$q->where('plan_produccion_id',$plan->id))->firstOrFail();
        DB::table('demoras_produccion')->insert($d+['plan_produccion_id'=>$plan->id,'registrado_por'=>auth()->user()?->name ?? 'Usuario autenticado','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Demora registrada sin cambiar las fechas planificadas.');
    }
    public function resolver(Request $r,PlanProduccion $plan,int $demora) {
        $d=$r->validate(['resolucion'=>['required','string','max:5000']]);
        DB::table('demoras_produccion')->where('id',$demora)->where('plan_produccion_id',$plan->id)->whereNull('resuelta_at')
            ->update(['resuelta_at'=>now(),'resuelta_por'=>auth()->user()?->name ?? 'Usuario autenticado','resolucion'=>$d['resolucion'],'updated_at'=>now()]);
        return back()->with('success','Demora resuelta. Se conserva el historial.');
    }
}
