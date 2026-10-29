<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\EtapaProduccion;
use App\Services\EstimadorEntrega;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TableroProduccionController extends Controller {
    private function fecha($fecha): ?CarbonImmutable {
        return $fecha ? CarbonImmutable::parse(CarbonImmutable::parse($fecha)->format('Y-m-d'),EstimadorEntrega::ZONA)->startOfDay() : null;
    }
    private function barra($inicio,$fin,CarbonImmutable $desde,CarbonImmutable $hasta,bool $enCurso=false): array {
        $inicio=$this->fecha($inicio); $fin=$this->fecha($fin);
        if(!$inicio && !$fin) return ['visible'=>false,'texto'=>'Sin fechas'];
        if($inicio && $fin && $fin->lt($inicio)) return ['visible'=>false,'texto'=>'Fechas invertidas: revisar'];
        $punto=!$inicio || !$fin;
        $inicio ??= $fin; $fin ??= $inicio;
        $texto=$punto ? $inicio->format('d/m/Y').' · fecha puntual' : $inicio->format('d/m/Y').' al '.$fin->format('d/m/Y');
        if($enCurso) $texto.=' · en curso';
        if($fin->lt($desde) || $inicio->gt($hasta))return ['visible'=>false,'texto'=>$texto.' · fuera del período'];
        $a=$inicio->max($desde);$z=$fin->min($hasta);
        return ['visible'=>true,'texto'=>$texto,'x'=>(int)$desde->diffInDays($a)*26,
            'ancho'=>((int)$a->diffInDays($z)+1)*26,'punto'=>$punto,'en_curso'=>$enCurso];
    }
    private function real($avances,$desde,$hasta,$hoy): array {
        $activos=$avances->whereIn('estado',['en_proceso','completada']);
        $inicio=$activos->pluck('fecha_inicio')->filter()->sort()->first();
        $enCurso=$activos->contains('estado','en_proceso');
        $fin=$enCurso && $inicio ? $hoy : $activos->pluck('fecha_fin')->filter()->sort()->last();
        return $this->barra($inicio,$fin,$desde,$hasta,$enCurso);
    }
    public function index(Request $r) {
        $hoy=CarbonImmutable::now(EstimadorEntrega::ZONA)->startOfDay();
        $r->merge(['desde'=>$r->input('desde') ?: $hoy->startOfMonth()->toDateString(),
            'hasta'=>$r->input('hasta') ?: $hoy->addMonthsNoOverflow(2)->endOfMonth()->toDateString()]);
        $filtros=$r->validate(['estado'=>['nullable','in:abierto,cerrado,cancelado'],
            'sector_gantt'=>['nullable',\Illuminate\Validation\Rule::in(\App\Services\SectoresProduccion::CODIGOS)],
            'plan_gantt'=>['nullable','integer','min:1'],
            'situacion_gantt'=>['nullable','in:sin_planificar,coincidencias,pendiente,en_proceso,completada'],
            'desde'=>['required','date'],'hasta'=>['required','date','after_or_equal:desde'],'page'=>['nullable','integer','min:1']]);
        if(!$r->has('estado'))$filtros['estado']='abierto';
        $desde=$this->fecha($filtros['desde']);$hasta=$this->fecha($filtros['hasta']);
        if($desde->diffInDays($hasta)>365)throw ValidationException::withMessages(['hasta'=>'El Gantt permite hasta 366 días por consulta.']);
        $planesTodos=PlanProduccion::with(['avances.etapa','planProductos.producto',
            'planProductos.unidadesProduccion.avances.etapa',
            'planProductos.unidadesProduccion.asignaciones.detalle.venta'])
            ->when(!empty($filtros['estado']),fn($q)=>$q->where('estado',$filtros['estado']))
            ->orderByRaw('fecha_planificada IS NULL')->orderBy('fecha_planificada')->orderBy('numero')->get();
        $estimador=new EstimadorEntrega;
        $etapas=EtapaProduccion::where('activo',true)->orderBy('orden')->get();
        $conteos=[];
        $totales=['planes'=>$planesTodos->count(),'unidades'=>0,'con_venta'=>0,'reservadas'=>0,'asignadas'=>0,'demoras'=>0,'vencidas'=>0,'sin_unidades'=>0];
        foreach($planesTodos as $plan){
            $unidades=collect();$ventas=['reservadas'=>0,'asignadas'=>0,'total'=>0];$estados=[];
            foreach($plan->planProductos as $linea){
                $linea->setRelation('plan',$plan);
                foreach($linea->unidadesProduccion as $unidad){
                    $unidad->setRelation('planProducto',$linea);
                    $eta=$estimador->calcular($unidad);$unidad->setAttribute('estimacion_entrega',$eta);
                    $nombre=$eta['excluida'] ? $eta['texto'] : $eta['etapa'];
                    $estados[$nombre]=($estados[$nombre]??0)+1;
                    $conteos[$nombre]=($conteos[$nombre]??0)+1;
                    $asignacion=$unidad->asignaciones->filter(fn($a)=>in_array($a->estado,['reservada','asignada']) && $a->detalle?->venta && $a->detalle->venta->estado!=='anulada')->sortByDesc('id')->first();
                    if($asignacion){$ventas[$asignacion->estado==='reservada'?'reservadas':'asignadas']++;$ventas['total']++;}
                    $totales['demoras']+=(int)!empty($eta['demoras_activas']);
                    $totales['vencidas']+=(int)!empty($eta['vencida']);
                    $unidades->push($unidad);
                }
            }
            $totales['unidades']+=$unidades->count();$totales['con_venta']+=$ventas['total'];
            $totales['reservadas']+=$ventas['reservadas'];$totales['asignadas']+=$ventas['asignadas'];
            if($unidades->isEmpty())$totales['sin_unidades']++;
            $general=$plan->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden??0)->first();
            $descripcion=$plan->observaciones ?: $plan->planProductos->map(fn($l)=>($l->producto?->nombre??'Producto no disponible').' × '.number_format((float)$l->cantidad,0,',','.'))->implode(' · ');
            $plan->setAttribute('tablero',['unidades'=>$unidades->count(),'ventas'=>$ventas,'estados'=>$estados,
                'general'=>$general?->etapa?->nombre ?? 'Planificado','descripcion'=>$descripcion ?: 'Sin descripción ni productos']);
        }
        $tarjetas=[];
        foreach($etapas as $etapa){
            if(isset($conteos[$etapa->nombre])){$tarjetas[]=['nombre'=>$etapa->nombre,'cantidad'=>$conteos[$etapa->nombre],'orden'=>$etapa->orden];unset($conteos[$etapa->nombre]);}
        }
        foreach($conteos as $nombre=>$cantidad)$tarjetas[]=['nombre'=>$nombre,'cantidad'=>$cantidad,'orden'=>99];
        $pagina=max(1,$r->integer('page',1));
        $planes=new LengthAwarePaginator($planesTodos->forPage($pagina,10)->values(),$planesTodos->count(),10,$pagina,
            ['path'=>route('dashboard'),'query'=>$r->query()]);
        $dias=collect();$meses=[];
        for($dia=$desde;$dia->lte($hasta);$dia=$dia->addDay()){
            $dias->push(['texto'=>$dia->format('d'),'fecha'=>$dia->format('d/m/Y'),'fin_semana'=>$dia->isWeekend(),'hoy'=>$dia->isSameDay($hoy)]);
            $mes=$dia->format('Y-m');
            if(!isset($meses[$mes]))$meses[$mes]=['nombre'=>$dia->locale('es')->translatedFormat('F Y'),'dias'=>0];
            $meses[$mes]['dias']++;
        }
        $gantt=[];
        foreach($planes as $plan){
            $unidades=$plan->planProductos->flatMap(fn($l)=>$l->unidadesProduccion);
            $avances=$plan->avances->concat($unidades->flatMap(fn($u)=>$u->avances));
            $pendientesDisponibilidad=$unidades->filter(fn($u)=>!$u->estimacion_entrega['excluida']);
            $disponibles=$pendientesDisponibilidad->filter(fn($u)=>$u->estimacion_entrega['fecha']);
            $sinEstimacion=$pendientesDisponibilidad->count()-$disponibles->count();
            $estimada=$disponibles->map(fn($u)=>$u->estimacion_entrega['fecha'])->sort()->last();
            $detalle=[];
            foreach(\App\Services\SectoresProduccion::ordenar($etapas->where('alcance','plan')->whereIn('codigo',\App\Services\SectoresProduccion::CODIGOS)) as $etapa){
                $avance=$plan->avances->firstWhere('etapa_id',$etapa->id);
                $detalle[]=['nombre'=>$etapa->nombre.' · plan','url'=>route('planes.avances',$plan),
                    'planificada'=>$this->barra($avance?->fecha_inicio_planificada,$avance?->fecha_fin_planificada,$desde,$hasta),
                    'real'=>$this->real(collect($avance?[$avance]:[]),$desde,$hasta,$hoy)];
            }
            foreach($unidades as $unidad){
                $actual=$unidad->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden??0)->first();
                $detalle[]=['nombre'=>'Chasis '.($unidad->chasis ?: 'sin cargar (#'.$unidad->id.')').' · '.$unidad->estimacion_entrega['etapa'],
                    'url'=>route('planes.productos.unidades.avances',[$plan,$unidad->planProducto,$unidad]),
                    'planificada'=>$this->barra($actual?->fecha_inicio_planificada,$actual?->fecha_fin_planificada,$desde,$hasta),
                    'real'=>$this->real(collect($actual?[$actual]:[]),$desde,$hasta,$hoy)];
            }
            $estimacionPlan=$sinEstimacion ? ['visible'=>false,'texto'=>'Plazo sin definir en '.$sinEstimacion.' unidades'] : $this->barra($estimada,null,$desde,$hasta);
            if($pendientesDisponibilidad->isEmpty())$estimacionPlan=['visible'=>false,'texto'=>$unidades->isEmpty() ? 'Sin unidades generadas' : 'Sin unidades pendientes'];
            $gantt[$plan->id]=['planificada'=>$this->barra($plan->fecha_planificada,$plan->fecha_fin_planificada,$desde,$hasta),
                'real'=>$this->real($avances,$desde,$hasta,$hoy),'estimada'=>$estimacionPlan,'detalle'=>$detalle];
        }
        // Cada etapa compara todos los planes filtrados, sin depender de la página.
        $sectoresGantt=\App\Services\SectoresProduccion::ordenar($etapas->where('alcance','plan')->whereIn('codigo',\App\Services\SectoresProduccion::CODIGOS));
        $planesGantt=$planesTodos->map(fn($p)=>['id'=>$p->id,'numero'=>$p->numero]);
        $ganttEtapas=[];
        foreach($sectoresGantt as $etapa){
            if(!empty($filtros['sector_gantt']) && $etapa->codigo!==$filtros['sector_gantt'])continue;
            $filas=[];
            foreach($planesTodos as $plan){
                $avance=$plan->avances->firstWhere('etapa_id',$etapa->id);
                $inicio=$avance?->fecha_inicio_planificada;
                $fin=$avance?->fecha_fin_planificada;
                $aviso= !$inicio && !$fin ? 'Sin planificar' : (!$inicio || !$fin ? 'Planificación incompleta' : ($fin->lt($inicio) ? 'Fechas invertidas: revisar' : null));
                $filas[]=['plan'=>$plan,'avance'=>$avance,'inicio'=>$inicio,'fin'=>$fin,'aviso'=>$aviso,'coincidencias'=>[],
                    'planificada'=>$this->barra($inicio,$fin,$desde,$hasta),
                    'real'=>$this->real(collect($avance?[$avance]:[]),$desde,$hasta,$hoy)];
            }
            // La comparación es por día visible; indica coincidencia, no falta de capacidad.
            foreach($filas as $i=>$fila){
                if($fila['aviso'] || $fila['plan']->estado==='cancelado')continue;
                foreach($filas as $j=>$otra){
                    if($j<=$i || $otra['aviso'] || $otra['plan']->estado==='cancelado')continue;
                    $a=$this->fecha($fila['inicio'])->max($this->fecha($otra['inicio']))->max($desde);
                    $z=$this->fecha($fila['fin'])->min($this->fecha($otra['fin']))->min($hasta);
                    if($a->lte($z)){
                        $filas[$i]['coincidencias'][]=$otra['plan']->numero;
                        $filas[$j]['coincidencias'][]=$fila['plan']->numero;
                    }
                }
            }
            // Filtrar después de detectar coincidencias conserva los otros planes como referencia.
            $filas=array_values(array_filter($filas,function($fila)use($filtros){
                if(!empty($filtros['plan_gantt']) && (int)$fila['plan']->id!==(int)$filtros['plan_gantt'])return false;
                $situacion=$filtros['situacion_gantt'] ?? '';
                if($situacion==='sin_planificar')return $fila['aviso']!==null;
                if($situacion==='coincidencias')return !empty($fila['coincidencias']);
                return !$situacion || ($fila['avance']?->estado ?? 'pendiente')===$situacion;
            }));
            if($filas)$ganttEtapas[]=['nombre'=>$etapa->nombre,'filas'=>$filas];
        }
        $ancho=$dias->count()*26;
        $hoyX=$hoy->betweenIncluded($desde,$hasta)?(int)$desde->diffInDays($hoy)*26:null;
        return view('dashboard',compact('planes','filtros','totales','tarjetas','dias','meses','gantt','ancho','hoyX','hoy','ganttEtapas','sectoresGantt','planesGantt'));
    }
}
